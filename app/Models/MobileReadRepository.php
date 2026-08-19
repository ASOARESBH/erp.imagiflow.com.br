<?php

namespace App\Models;

use App\Core\Model;
use App\Core\TenantContext;
use PDO;

/**
 * Read model da API móvel. Mantém as consultas de listagem e indicadores fora dos
 * controllers e aplica tenant_id em todos os domínios operacionais.
 */
class MobileReadRepository extends Model
{
    public function clients(string $query, string $status, int $page, int $perPage): array
    {
        $where = ['c.tenant_id = :tenant_id'];
        $params = [':tenant_id' => TenantContext::id()];
        if ($status !== '' && $status !== 'todos') {
            $where[] = 'c.status = :status';
            $params[':status'] = $status;
        }
        if ($query !== '') {
            $where[] = '(c.razao_social LIKE :q OR c.nome_fantasia LIKE :q OR c.cpf_cnpj LIKE :q)';
            $params[':q'] = '%' . $query . '%';
        }
        return $this->page(
            'FROM clientes c WHERE ' . implode(' AND ', $where),
            $params,
            'c.id, c.tipo, c.cpf_cnpj, c.razao_social, c.nome_fantasia, c.email, c.telefone, c.celular, c.status, c.created_at',
            'c.id DESC',
            $page,
            $perPage
        );
    }

    public function client(int $id): object|false
    {
        return $this->one('SELECT * FROM clientes WHERE id = :id AND tenant_id = :tenant_id', [':id' => $id]);
    }

    public function clientContacts(int $clientId): array
    {
        return $this->all(
            'SELECT * FROM clientes_contatos WHERE cliente_id = :client_id AND tenant_id = :tenant_id ORDER BY id DESC',
            [':client_id' => $clientId]
        );
    }

    public function vendors(string $query, string $status, int $page, int $perPage): array
    {
        $where = ['f.tenant_id = :tenant_id'];
        $params = [':tenant_id' => TenantContext::id()];
        if ($status !== '' && $status !== 'todos') {
            $where[] = 'f.status = :status';
            $params[':status'] = $status;
        }
        if ($query !== '') {
            $where[] = '(f.nome LIKE :q OR f.nome_fantasia LIKE :q OR f.documento LIKE :q OR f.email LIKE :q)';
            $params[':q'] = '%' . $query . '%';
        }
        return $this->page(
            'FROM fornecedores f WHERE ' . implode(' AND ', $where),
            $params,
            'f.id, f.tipo, f.nome, f.nome_fantasia, f.documento, f.email, f.telefone, f.celular, f.status, f.created_at',
            'f.created_at DESC, f.id DESC',
            $page,
            $perPage
        );
    }

    public function vendor(int $id): object|false
    {
        return $this->one('SELECT * FROM fornecedores WHERE id = :id AND tenant_id = :tenant_id', [':id' => $id]);
    }

    public function payables(string $query, string $status, int $page, int $perPage): array
    {
        $where = ['cp.tenant_id = :tenant_id'];
        $params = [':tenant_id' => TenantContext::id()];
        if ($status !== '' && $status !== 'todos') {
            $where[] = 'cp.status = :status';
            $params[':status'] = $status;
        }
        if ($query !== '') {
            $where[] = '(cp.descricao LIKE :q OR f.nome LIKE :q)';
            $params[':q'] = '%' . $query . '%';
        }
        return $this->page(
            'FROM contas_pagar cp LEFT JOIN fornecedores f ON f.id = cp.fornecedor_id AND f.tenant_id = cp.tenant_id WHERE ' . implode(' AND ', $where),
            $params,
            'cp.*, f.nome AS fornecedor_nome',
            'cp.data_vencimento ASC, cp.id ASC',
            $page,
            $perPage
        );
    }

    public function receivables(string $query, string $status, int $page, int $perPage): array
    {
        $where = ['cr.tenant_id = :tenant_id'];
        $params = [':tenant_id' => TenantContext::id()];
        if ($status !== '' && $status !== 'todos') {
            $where[] = 'cr.status = :status';
            $params[':status'] = $status;
        }
        if ($query !== '') {
            $where[] = '(cr.descricao LIKE :q OR c.razao_social LIKE :q OR c.nome_fantasia LIKE :q)';
            $params[':q'] = '%' . $query . '%';
        }
        return $this->page(
            'FROM contas_receber cr LEFT JOIN clientes c ON c.id = cr.cliente_id AND c.tenant_id = cr.tenant_id WHERE ' . implode(' AND ', $where),
            $params,
            'cr.*, c.razao_social AS cliente_nome, c.nome_fantasia AS cliente_fantasia',
            'cr.data_vencimento ASC, cr.id ASC',
            $page,
            $perPage
        );
    }

    public function financialSummary(): array
    {
        $tenantId = TenantContext::id();
        $payables = $this->one(
            "SELECT
                COALESCE(SUM(CASE WHEN status = 'aberta' THEN valor ELSE 0 END), 0) AS open_total,
                COALESCE(SUM(CASE WHEN status = 'aberta' AND data_vencimento < CURDATE() THEN valor ELSE 0 END), 0) AS overdue_total,
                COALESCE(SUM(CASE WHEN status = 'paga' THEN valor ELSE 0 END), 0) AS paid_total
             FROM contas_pagar WHERE tenant_id = :tenant_id",
            [':tenant_id' => $tenantId]
        );
        $receivables = $this->one(
            "SELECT
                COALESCE(SUM(CASE WHEN status = 'aberta' THEN valor ELSE 0 END), 0) AS open_total,
                COALESCE(SUM(CASE WHEN status = 'aberta' AND data_vencimento < CURDATE() THEN valor ELSE 0 END), 0) AS overdue_total,
                COALESCE(SUM(CASE WHEN status = 'recebida' THEN valor ELSE 0 END), 0) AS received_total
             FROM contas_receber WHERE tenant_id = :tenant_id",
            [':tenant_id' => $tenantId]
        );
        return ['contas_pagar' => $payables ?: (object) [], 'contas_receber' => $receivables ?: (object) []];
    }

    public function contracts(string $query, int $page, int $perPage): array
    {
        $where = ['ct.tenant_id = :tenant_id'];
        $params = [':tenant_id' => TenantContext::id()];
        if ($query !== '') {
            $where[] = '(ct.numero LIKE :q OR ct.nome LIKE :q OR c.razao_social LIKE :q)';
            $params[':q'] = '%' . $query . '%';
        }
        return $this->page(
            'FROM contratos ct LEFT JOIN clientes c ON c.id = ct.cliente_id AND c.tenant_id = ct.tenant_id WHERE ' . implode(' AND ', $where),
            $params,
            'ct.*, c.razao_social AS cliente_nome',
            'ct.data_inicio DESC, ct.id DESC',
            $page,
            $perPage
        );
    }

    public function contract(int $id): object|false
    {
        return $this->one(
            'SELECT ct.*, c.razao_social AS cliente_nome
             FROM contratos ct LEFT JOIN clientes c ON c.id = ct.cliente_id AND c.tenant_id = ct.tenant_id
             WHERE ct.id = :id AND ct.tenant_id = :tenant_id',
            [':id' => $id]
        );
    }

    public function apuracoes(string $type, int $page, int $perPage): array
    {
        $where = ['a.tenant_id = :tenant_id'];
        $params = [':tenant_id' => TenantContext::id()];
        if (in_array($type, ['cliente', 'prestador'], true)) {
            $where[] = 'a.tipo = :tipo';
            $params[':tipo'] = $type;
        }
        return $this->page(
            'FROM apuracoes a INNER JOIN contratos ct ON ct.id = a.contrato_id AND ct.tenant_id = a.tenant_id WHERE ' . implode(' AND ', $where),
            $params,
            'a.*, ct.numero AS contrato_numero, ct.nome AS contrato_nome',
            'a.created_at DESC, a.id DESC',
            $page,
            $perPage
        );
    }

    public function leads(string $query, int $page, int $perPage): array
    {
        $where = ['l.tenant_id = :tenant_id'];
        $params = [':tenant_id' => TenantContext::id()];
        if ($query !== '') {
            $where[] = '(l.nome_lead LIKE :q OR l.razao_social LIKE :q OR l.email LIKE :q OR l.cnpj LIKE :q)';
            $params[':q'] = '%' . $query . '%';
        }
        return $this->page('FROM crm_leads l WHERE ' . implode(' AND ', $where), $params, 'l.*', 'l.created_at DESC, l.id DESC', $page, $perPage);
    }

    public function opportunities(string $query, string $stage, int $page, int $perPage): array
    {
        $where = ['o.tenant_id = :tenant_id'];
        $params = [':tenant_id' => TenantContext::id()];
        if ($stage !== '') {
            $where[] = 'o.etapa_funil = :stage';
            $params[':stage'] = $stage;
        }
        if ($query !== '') {
            $where[] = '(o.titulo_oportunidade LIKE :q OR l.nome_lead LIKE :q OR c.razao_social LIKE :q)';
            $params[':q'] = '%' . $query . '%';
        }
        return $this->page(
            'FROM crm_oportunidades o
             LEFT JOIN crm_leads l ON l.id = o.lead_id AND l.tenant_id = o.tenant_id
             LEFT JOIN clientes c ON c.id = o.cliente_id AND c.tenant_id = o.tenant_id
             WHERE ' . implode(' AND ', $where),
            $params,
            'o.*, l.nome_lead, c.razao_social AS cliente_nome',
            'o.data_fechamento_prevista ASC, o.id DESC',
            $page,
            $perPage
        );
    }

    public function crmPipeline(): array
    {
        return $this->all(
            "SELECT etapa_funil, COUNT(*) AS total, COALESCE(SUM(valor_estimado), 0) AS valor_estimado
             FROM crm_oportunidades
             WHERE tenant_id = :tenant_id AND status_oportunidade = 'aberta'
             GROUP BY etapa_funil
             ORDER BY FIELD(etapa_funil, 'qualificacao', 'proposta', 'negociacao', 'fechamento')",
            []
        );
    }

    public function proposals(string $query, int $page, int $perPage): array
    {
        $where = ['p.tenant_id = :tenant_id'];
        $params = [':tenant_id' => TenantContext::id()];
        if ($query !== '') {
            $where[] = '(p.numero LIKE :q OR p.titulo LIKE :q OR p.cliente_nome LIKE :q)';
            $params[':q'] = '%' . $query . '%';
        }
        return $this->page('FROM crm_propostas p WHERE ' . implode(' AND ', $where), $params, 'p.*', 'p.created_at DESC, p.id DESC', $page, $perPage);
    }

    public function interactions(string $relatedType, int $relatedId): array
    {
        return $this->all(
            'SELECT i.*, u.name AS user_name
             FROM crm_interacoes i LEFT JOIN users u ON u.id = i.usuario_id
             WHERE i.tenant_id = :tenant_id AND i.related_type = :related_type AND i.related_id = :related_id
             ORDER BY i.data_interacao DESC, i.id DESC',
            [':related_type' => $relatedType, ':related_id' => $relatedId]
        );
    }

    public function maintenanceOrders(string $query, string $status, int $page, int $perPage): array
    {
        $where = ['os.tenant_id = :tenant_id'];
        $params = [':tenant_id' => TenantContext::id()];
        if ($status !== '' && $status !== 'todos') {
            $where[] = 'os.status = :status';
            $params[':status'] = $status;
        }
        if ($query !== '') {
            $where[] = '(os.numero_os LIKE :q OR os.descricao_problema LIKE :q)';
            $params[':q'] = '%' . $query . '%';
        }
        return $this->page('FROM manut_ordens_servico os WHERE ' . implode(' AND ', $where), $params, 'os.*', 'os.created_at DESC, os.id DESC', $page, $perPage);
    }

    public function trips(string $query, string $status, int $page, int $perPage): array
    {
        $where = ['v.tenant_id = :tenant_id'];
        $params = [':tenant_id' => TenantContext::id()];
        if ($status !== '' && $status !== 'todos') {
            $where[] = 'v.status = :status';
            $params[':status'] = $status;
        }
        if ($query !== '') {
            $where[] = '(v.nome LIKE :q OR v.codigo LIKE :q OR r.nome LIKE :q)';
            $params[':q'] = '%' . $query . '%';
        }
        return $this->page(
            'FROM rdv_viagens v LEFT JOIN rdv_rotas r ON r.id = v.rota_id AND r.tenant_id = v.tenant_id WHERE ' . implode(' AND ', $where),
            $params,
            'v.*, r.nome AS rota_nome',
            'v.created_at DESC, v.id DESC',
            $page,
            $perPage
        );
    }

    public function notifications(int $userId, int $page, int $perPage): array
    {
        return $this->page(
            'FROM notificacoes n WHERE n.tenant_id = :tenant_id AND n.usuario_id = :user_id',
            [':user_id' => $userId],
            'n.*',
            'n.created_at DESC, n.id DESC',
            $page,
            $perPage
        );
    }

    public function dashboard(): array
    {
        $tenantId = TenantContext::id();
        return [
            'clientes_ativos' => $this->scalar("SELECT COUNT(*) FROM clientes WHERE tenant_id = :tenant_id AND status = 'ativo'", [':tenant_id' => $tenantId]),
            'contas_pagar_vencidas' => $this->scalar("SELECT COUNT(*) FROM contas_pagar WHERE tenant_id = :tenant_id AND status = 'aberta' AND data_vencimento < CURDATE()", [':tenant_id' => $tenantId]),
            'contas_receber_vencidas' => $this->scalar("SELECT COUNT(*) FROM contas_receber WHERE tenant_id = :tenant_id AND status = 'aberta' AND data_vencimento < CURDATE()", [':tenant_id' => $tenantId]),
            'leads_abertos' => $this->scalar("SELECT COUNT(*) FROM crm_leads WHERE tenant_id = :tenant_id AND status_lead NOT IN ('descartado')", [':tenant_id' => $tenantId]),
            'os_pendentes' => $this->scalar("SELECT COUNT(*) FROM manut_ordens_servico WHERE tenant_id = :tenant_id AND status NOT IN ('concluida', 'cancelada')", [':tenant_id' => $tenantId]),
            'viagens_abertas' => $this->scalar("SELECT COUNT(*) FROM rdv_viagens WHERE tenant_id = :tenant_id AND status IN ('aberto', 'iniciado')", [':tenant_id' => $tenantId]),
            'financeiro' => $this->financialSummary(),
        ];
    }

    public function search(string $query, array $permissions, int $limit = 5): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }
        $limit = min(20, max(1, $limit));
        $like = '%' . $query . '%';
        $result = [];
        if (in_array('view_clients', $permissions, true)) {
            $result['clientes'] = $this->limited(
                'SELECT id, COALESCE(nome_fantasia, razao_social) AS title, cpf_cnpj AS subtitle FROM clientes WHERE tenant_id = :tenant_id AND (razao_social LIKE :q OR nome_fantasia LIKE :q OR cpf_cnpj LIKE :q) ORDER BY id DESC',
                [':q' => $like],
                $limit
            );
        }
        if (in_array('view_crm', $permissions, true)) {
            $result['leads'] = $this->limited(
                'SELECT id, nome_lead AS title, email AS subtitle FROM crm_leads WHERE tenant_id = :tenant_id AND (nome_lead LIKE :q OR email LIKE :q) ORDER BY id DESC',
                [':q' => $like],
                $limit
            );
            $result['oportunidades'] = $this->limited(
                'SELECT id, titulo_oportunidade AS title, etapa_funil AS subtitle FROM crm_oportunidades WHERE tenant_id = :tenant_id AND titulo_oportunidade LIKE :q ORDER BY id DESC',
                [':q' => $like],
                $limit
            );
        }
        if (in_array('view_manutencao', $permissions, true)) {
            $result['ordens_servico'] = $this->limited(
                'SELECT id, numero_os AS title, status AS subtitle FROM manut_ordens_servico WHERE tenant_id = :tenant_id AND numero_os LIKE :q ORDER BY id DESC',
                [':q' => $like],
                $limit
            );
        }
        return $result;
    }

    private function page(string $fromWhere, array $params, string $select, string $orderBy, int $page, int $perPage): array
    {
        $page = max(1, $page);
        $perPage = min(100, max(1, $perPage));
        $params[':tenant_id'] = TenantContext::id();
        $countStmt = $this->pdo->prepare('SELECT COUNT(*) ' . $fromWhere);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $sql = 'SELECT ' . $select . ' ' . $fromWhere . ' ORDER BY ' . $orderBy . ' LIMIT ' . $perPage . ' OFFSET ' . $offset;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return ['items' => $stmt->fetchAll(PDO::FETCH_OBJ), 'total' => $total];
    }

    private function all(string $sql, array $params): array
    {
        $params[':tenant_id'] = TenantContext::id();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    private function one(string $sql, array $params): object|false
    {
        $params[':tenant_id'] = TenantContext::id();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: false;
    }

    private function scalar(string $sql, array $params): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    private function limited(string $sql, array $params, int $limit): array
    {
        $params[':tenant_id'] = TenantContext::id();
        $stmt = $this->pdo->prepare($sql . ' LIMIT ' . $limit);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}
