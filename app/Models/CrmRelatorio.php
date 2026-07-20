<?php

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * CrmRelatorio — consultas analíticas do módulo CRM.
 *
 * Compatível com MySQL 5.7 (sem CTEs, sem Window Functions).
 * Todas as queries usam Prepared Statements.
 */
class CrmRelatorio extends Model
{
    // ---------------------------------------------------------------
    // Helpers internos
    // ---------------------------------------------------------------

    /**
     * Monta cláusula WHERE + params para filtros comuns de período e usuário.
     * Prefixo de tabela: 'l' para leads, 'o' para oportunidades.
     */
    private function buildWhere(array $filtros, string $prefixo = 'o', string $campoData = 'created_at'): array
    {
        $where  = [];
        $params = [];

        if (!empty($filtros['usuario_id']) && (int)$filtros['usuario_id'] > 0) {
            $where[]              = "{$prefixo}.usuario_id = :uid";
            $params[':uid']       = (int)$filtros['usuario_id'];
        }
        if (!empty($filtros['data_inicio'])) {
            $where[]                  = "DATE({$prefixo}.{$campoData}) >= :data_inicio";
            $params[':data_inicio']   = $filtros['data_inicio'];
        }
        if (!empty($filtros['data_fim'])) {
            $where[]                = "DATE({$prefixo}.{$campoData}) <= :data_fim";
            $params[':data_fim']    = $filtros['data_fim'];
        }

        return [$where, $params];
    }

    // ---------------------------------------------------------------
    // RELATÓRIO DE LEADS
    // ---------------------------------------------------------------

    /**
     * Lista detalhada de leads com totais de interações.
     * Filtros: usuario_id, status_lead, origem, segmento, data_inicio, data_fim.
     */
    public function listarLeads(array $filtros = []): array
    {
        [$where, $params] = $this->buildWhere($filtros, 'l', 'created_at');

        if (!empty($filtros['status_lead'])) {
            $where[]              = 'l.status_lead = :status_lead';
            $params[':status_lead'] = $filtros['status_lead'];
        }
        if (!empty($filtros['origem'])) {
            $where[]           = 'l.origem = :origem';
            $params[':origem'] = $filtros['origem'];
        }
        if (!empty($filtros['segmento'])) {
            $where[]             = 'l.segmento_principal = :segmento';
            $params[':segmento'] = $filtros['segmento'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT
                    l.id,
                    l.nome_lead,
                    l.razao_social,
                    l.email,
                    l.telefone,
                    l.cidade,
                    l.estado,
                    l.status_lead,
                    l.origem,
                    l.segmento_principal,
                    l.data_proximo_contato,
                    l.created_at,
                    u.name AS responsavel,
                    (SELECT COUNT(*) FROM crm_interacoes i
                     WHERE i.related_type = 'lead' AND i.related_id = l.id) AS total_interacoes,
                    (SELECT MAX(i2.data_interacao) FROM crm_interacoes i2
                     WHERE i2.related_type = 'lead' AND i2.related_id = l.id) AS ultima_interacao
                FROM crm_leads l
                LEFT JOIN users u ON u.id = l.usuario_id
                {$whereClause}
                ORDER BY l.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * KPIs de Leads: totais por status, por origem e por segmento.
     */
    public function kpisLeads(array $filtros = []): array
    {
        [$where, $params] = $this->buildWhere($filtros, 'l', 'created_at');
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Por status
        $stmt = $this->pdo->prepare(
            "SELECT status_lead, COUNT(*) AS total
             FROM crm_leads l {$whereClause}
             GROUP BY status_lead"
        );
        $stmt->execute($params);
        $porStatus = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Por origem
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(origem, 'outro') AS origem, COUNT(*) AS total
             FROM crm_leads l {$whereClause}
             GROUP BY origem
             ORDER BY total DESC"
        );
        $stmt->execute($params);
        $porOrigem = $stmt->fetchAll(PDO::FETCH_OBJ);

        // Por segmento
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(segmento_principal, 'outro') AS segmento, COUNT(*) AS total
             FROM crm_leads l {$whereClause}
             GROUP BY segmento_principal
             ORDER BY total DESC"
        );
        $stmt->execute($params);
        $porSegmento = $stmt->fetchAll(PDO::FETCH_OBJ);

        // Total geral
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM crm_leads l {$whereClause}");
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        // Leads com próximo contato vencido
        $whereVencido = !empty($where)
            ? $whereClause . " AND l.data_proximo_contato < CURDATE()"
            : "WHERE l.data_proximo_contato < CURDATE()";
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM crm_leads l {$whereVencido}");
        $stmt->execute($params);
        $vencidos = (int)$stmt->fetchColumn();

        return compact('total', 'porStatus', 'porOrigem', 'porSegmento', 'vencidos');
    }

    /**
     * Evolução mensal de leads criados (últimos 12 meses).
     */
    public function evolucaoMensalLeads(array $filtros = []): array
    {
        [$where, $params] = $this->buildWhere($filtros, 'l', 'created_at');
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT
                    DATE_FORMAT(l.created_at, '%Y-%m') AS mes,
                    COUNT(*) AS total
                FROM crm_leads l
                {$whereClause}
                GROUP BY DATE_FORMAT(l.created_at, '%Y-%m')
                ORDER BY mes ASC
                LIMIT 12";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // ---------------------------------------------------------------
    // RELATÓRIO DE OPORTUNIDADES
    // ---------------------------------------------------------------

    /**
     * Lista detalhada de oportunidades com dados do lead/cliente e interações.
     * Filtros: usuario_id, etapa_funil, status_oportunidade, tipo_contrato, data_inicio, data_fim.
     */
    public function listarOportunidades(array $filtros = []): array
    {
        [$where, $params] = $this->buildWhere($filtros, 'o', 'created_at');

        if (!empty($filtros['etapa_funil'])) {
            $where[]             = 'o.etapa_funil = :etapa';
            $params[':etapa']    = $filtros['etapa_funil'];
        }
        if (!empty($filtros['status_oportunidade'])) {
            $where[]             = 'o.status_oportunidade = :status_op';
            $params[':status_op'] = $filtros['status_oportunidade'];
        }
        if (!empty($filtros['tipo_contrato'])) {
            $where[]                  = 'o.tipo_contrato = :tipo_contrato';
            $params[':tipo_contrato'] = $filtros['tipo_contrato'];
        }
        if (!empty($filtros['modalidade'])) {
            $where[]               = 'o.modalidade_principal = :modalidade';
            $params[':modalidade'] = $filtros['modalidade'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT
                    o.id,
                    o.titulo_oportunidade,
                    o.etapa_funil,
                    o.status_oportunidade,
                    o.valor_estimado,
                    o.probabilidade_sucesso,
                    o.tipo_contrato,
                    o.modalidade_principal,
                    o.volume_estimado_mes,
                    o.data_fechamento_prevista,
                    o.data_proximo_contato,
                    o.motivo_perda,
                    o.created_at,
                    COALESCE(l.nome_lead, c.razao_social) AS nome_contato,
                    l.email AS lead_email,
                    l.segmento_principal AS lead_segmento,
                    c.razao_social AS cliente_razao_social,
                    u.name AS responsavel,
                    (SELECT COUNT(*) FROM crm_interacoes i
                     WHERE i.related_type = 'oportunidade' AND i.related_id = o.id) AS total_interacoes,
                    (SELECT MAX(i2.data_interacao) FROM crm_interacoes i2
                     WHERE i2.related_type = 'oportunidade' AND i2.related_id = o.id) AS ultima_interacao,
                    (SELECT COUNT(*) FROM crm_propostas p
                     WHERE p.oportunidade_id = o.id) AS total_propostas
                FROM crm_oportunidades o
                LEFT JOIN crm_leads l ON l.id = o.lead_id
                LEFT JOIN clientes  c ON c.id = o.cliente_id
                LEFT JOIN users     u ON u.id = o.usuario_id
                {$whereClause}
                ORDER BY o.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * KPIs de Oportunidades: totais, valores, taxas de conversão.
     */
    public function kpisOportunidades(array $filtros = []): array
    {
        [$where, $params] = $this->buildWhere($filtros, 'o', 'created_at');
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Totais por status
        $stmt = $this->pdo->prepare(
            "SELECT status_oportunidade, COUNT(*) AS total,
                    COALESCE(SUM(valor_estimado), 0) AS valor_total
             FROM crm_oportunidades o {$whereClause}
             GROUP BY status_oportunidade"
        );
        $stmt->execute($params);
        $porStatus = [];
        foreach ($stmt->fetchAll(PDO::FETCH_OBJ) as $row) {
            $porStatus[$row->status_oportunidade] = $row;
        }

        // Totais por etapa (apenas abertas)
        $whereEtapa = !empty($where)
            ? $whereClause . " AND o.status_oportunidade = 'aberta'"
            : "WHERE o.status_oportunidade = 'aberta'";
        $stmt = $this->pdo->prepare(
            "SELECT etapa_funil, COUNT(*) AS total,
                    COALESCE(SUM(valor_estimado), 0) AS valor_total
             FROM crm_oportunidades o {$whereEtapa}
             GROUP BY etapa_funil"
        );
        $stmt->execute($params);
        $porEtapa = [];
        foreach ($stmt->fetchAll(PDO::FETCH_OBJ) as $row) {
            $porEtapa[$row->etapa_funil] = $row;
        }

        // Totais por tipo de contrato
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(tipo_contrato, 'outro') AS tipo_contrato,
                    COUNT(*) AS total,
                    COALESCE(SUM(valor_estimado), 0) AS valor_total
             FROM crm_oportunidades o {$whereClause}
             GROUP BY tipo_contrato
             ORDER BY valor_total DESC"
        );
        $stmt->execute($params);
        $porTipoContrato = $stmt->fetchAll(PDO::FETCH_OBJ);

        // Motivos de perda
        $wherePerdida = !empty($where)
            ? $whereClause . " AND o.status_oportunidade = 'perdida' AND o.motivo_perda IS NOT NULL"
            : "WHERE o.status_oportunidade = 'perdida' AND o.motivo_perda IS NOT NULL";
        $stmt = $this->pdo->prepare(
            "SELECT motivo_perda, COUNT(*) AS total
             FROM crm_oportunidades o {$wherePerdida}
             GROUP BY motivo_perda
             ORDER BY total DESC"
        );
        $stmt->execute($params);
        $motivosPerda = $stmt->fetchAll(PDO::FETCH_OBJ);

        // Valor total pipeline (abertas)
        $totalAberta  = $porStatus['aberta']->total       ?? 0;
        $valorAberta  = $porStatus['aberta']->valor_total ?? 0;
        $totalGanha   = $porStatus['ganha']->total        ?? 0;
        $valorGanha   = $porStatus['ganha']->valor_total  ?? 0;
        $totalPerdida = $porStatus['perdida']->total      ?? 0;
        $valorPerdida = $porStatus['perdida']->valor_total ?? 0;
        $totalGeral   = $totalAberta + $totalGanha + $totalPerdida;

        // Taxa de conversão (ganhas / total fechadas)
        $totalFechadas = $totalGanha + $totalPerdida;
        $taxaConversao = $totalFechadas > 0
            ? round(($totalGanha / $totalFechadas) * 100, 1)
            : 0;

        // Ticket médio (oportunidades ganhas)
        $ticketMedio = $totalGanha > 0 ? round($valorGanha / $totalGanha, 2) : 0;

        return compact(
            'totalGeral', 'totalAberta', 'valorAberta',
            'totalGanha', 'valorGanha',
            'totalPerdida', 'valorPerdida',
            'taxaConversao', 'ticketMedio',
            'porEtapa', 'porTipoContrato', 'motivosPerda'
        );
    }

    /**
     * Evolução mensal de oportunidades criadas e valor (últimos 12 meses).
     */
    public function evolucaoMensalOportunidades(array $filtros = []): array
    {
        [$where, $params] = $this->buildWhere($filtros, 'o', 'created_at');
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT
                    DATE_FORMAT(o.created_at, '%Y-%m') AS mes,
                    COUNT(*) AS total,
                    COALESCE(SUM(o.valor_estimado), 0) AS valor_total,
                    SUM(CASE WHEN o.status_oportunidade = 'ganha'   THEN 1 ELSE 0 END) AS ganhas,
                    SUM(CASE WHEN o.status_oportunidade = 'perdida' THEN 1 ELSE 0 END) AS perdidas
                FROM crm_oportunidades o
                {$whereClause}
                GROUP BY DATE_FORMAT(o.created_at, '%Y-%m')
                ORDER BY mes ASC
                LIMIT 12";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Ranking de responsáveis por oportunidades ganhas e valor.
     */
    public function rankingResponsaveis(array $filtros = []): array
    {
        [$where, $params] = $this->buildWhere($filtros, 'o', 'created_at');
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT
                    u.name AS responsavel,
                    COUNT(*) AS total_oportunidades,
                    SUM(CASE WHEN o.status_oportunidade = 'ganha'   THEN 1 ELSE 0 END) AS ganhas,
                    SUM(CASE WHEN o.status_oportunidade = 'perdida' THEN 1 ELSE 0 END) AS perdidas,
                    SUM(CASE WHEN o.status_oportunidade = 'aberta'  THEN 1 ELSE 0 END) AS abertas,
                    COALESCE(SUM(CASE WHEN o.status_oportunidade = 'ganha' THEN o.valor_estimado END), 0) AS valor_ganho,
                    COALESCE(SUM(o.valor_estimado), 0) AS valor_pipeline
                FROM crm_oportunidades o
                LEFT JOIN users u ON u.id = o.usuario_id
                {$whereClause}
                GROUP BY o.usuario_id, u.name
                ORDER BY valor_ganho DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // ---------------------------------------------------------------
    // RELATÓRIO DE INTERAÇÕES
    // ---------------------------------------------------------------

    /**
     * Lista detalhada de interações (leads + oportunidades).
     * Filtros: usuario_id, tipo_interacao, related_type, data_inicio, data_fim.
     */
    public function listarInteracoes(array $filtros = []): array
    {
        $where  = [];
        $params = [];

        if (!empty($filtros['usuario_id']) && (int)$filtros['usuario_id'] > 0) {
            $where[]        = 'i.usuario_id = :uid';
            $params[':uid'] = (int)$filtros['usuario_id'];
        }
        if (!empty($filtros['tipo_interacao'])) {
            $where[]                  = 'i.tipo_interacao = :tipo';
            $params[':tipo']          = $filtros['tipo_interacao'];
        }
        if (!empty($filtros['related_type'])) {
            $where[]                  = 'i.related_type = :rtype';
            $params[':rtype']         = $filtros['related_type'];
        }
        if (!empty($filtros['data_inicio'])) {
            $where[]                  = 'DATE(i.data_interacao) >= :data_inicio';
            $params[':data_inicio']   = $filtros['data_inicio'];
        }
        if (!empty($filtros['data_fim'])) {
            $where[]                = 'DATE(i.data_interacao) <= :data_fim';
            $params[':data_fim']    = $filtros['data_fim'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT
                    i.id,
                    i.related_type,
                    i.related_id,
                    i.tipo_interacao,
                    i.data_interacao,
                    i.data_retorno,
                    LEFT(i.resumo, 200) AS resumo,
                    u.name AS responsavel,
                    CASE
                        WHEN i.related_type = 'lead' THEN l.nome_lead
                        WHEN i.related_type = 'oportunidade' THEN o.titulo_oportunidade
                        ELSE NULL
                    END AS entidade_nome
                FROM crm_interacoes i
                LEFT JOIN users u ON u.id = i.usuario_id
                LEFT JOIN crm_leads l
                    ON l.id = i.related_id AND i.related_type = 'lead'
                LEFT JOIN crm_oportunidades o
                    ON o.id = i.related_id AND i.related_type = 'oportunidade'
                {$whereClause}
                ORDER BY i.data_interacao DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * KPIs de interações: totais por tipo e por mês.
     */
    public function kpisInteracoes(array $filtros = []): array
    {
        $where  = [];
        $params = [];

        if (!empty($filtros['usuario_id']) && (int)$filtros['usuario_id'] > 0) {
            $where[]        = 'i.usuario_id = :uid';
            $params[':uid'] = (int)$filtros['usuario_id'];
        }
        if (!empty($filtros['data_inicio'])) {
            $where[]                  = 'DATE(i.data_interacao) >= :data_inicio';
            $params[':data_inicio']   = $filtros['data_inicio'];
        }
        if (!empty($filtros['data_fim'])) {
            $where[]                = 'DATE(i.data_interacao) <= :data_fim';
            $params[':data_fim']    = $filtros['data_fim'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Por tipo
        $stmt = $this->pdo->prepare(
            "SELECT tipo_interacao, COUNT(*) AS total
             FROM crm_interacoes i {$whereClause}
             GROUP BY tipo_interacao
             ORDER BY total DESC"
        );
        $stmt->execute($params);
        $porTipo = $stmt->fetchAll(PDO::FETCH_OBJ);

        // Por mês (últimos 12 meses)
        $sql = "SELECT
                    DATE_FORMAT(i.data_interacao, '%Y-%m') AS mes,
                    COUNT(*) AS total,
                    SUM(CASE WHEN i.related_type = 'lead'         THEN 1 ELSE 0 END) AS total_leads,
                    SUM(CASE WHEN i.related_type = 'oportunidade' THEN 1 ELSE 0 END) AS total_oportunidades
                FROM crm_interacoes i
                {$whereClause}
                GROUP BY DATE_FORMAT(i.data_interacao, '%Y-%m')
                ORDER BY mes ASC
                LIMIT 12";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $porMes = $stmt->fetchAll(PDO::FETCH_OBJ);

        // Total geral
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM crm_interacoes i {$whereClause}");
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        return compact('total', 'porTipo', 'porMes');
    }

    // ---------------------------------------------------------------
    // RELATÓRIO CONSOLIDADO (Dashboard de Relatórios)
    // ---------------------------------------------------------------

    /**
     * Retorna todos os usuários que possuem leads ou oportunidades.
     * Usado pelo seletor de usuário para admin/superadmin.
     */
    public function findUsuariosAtivos(): array
    {
        $sql = "SELECT DISTINCT u.id, u.name
                FROM users u
                WHERE u.id IN (
                    SELECT usuario_id FROM crm_leads
                    UNION
                    SELECT usuario_id FROM crm_oportunidades
                )
                ORDER BY u.name ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}
