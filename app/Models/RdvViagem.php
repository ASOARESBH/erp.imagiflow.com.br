<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Logger;

class RdvViagem
{
    private \PDO   $pdo;
    private Logger $logger;
    private string $table    = 'rdv_viagens';
    private string $tableSeq = 'rdv_seq';

    public function __construct()
    {
        $this->pdo    = Database::getInstance();
        $this->logger = new Logger();
    }

    // =========================================================================
    // Geração de código sequencial  RDV-2026-00001
    // =========================================================================
    public function gerarCodigo(int $uid): string
    {
        $ano = (int) date('Y');
        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare(
                "SELECT ultimo_numero FROM {$this->tableSeq}
                 WHERE usuario_id = :u AND ano = :a FOR UPDATE"
            );
            $stmt->execute([':u' => $uid, ':a' => $ano]);
            $row = $stmt->fetch(\PDO::FETCH_OBJ);
            if ($row) {
                $proximo = (int)$row->ultimo_numero + 1;
                $this->pdo->prepare(
                    "UPDATE {$this->tableSeq} SET ultimo_numero = :n
                     WHERE usuario_id = :u AND ano = :a"
                )->execute([':n' => $proximo, ':u' => $uid, ':a' => $ano]);
            } else {
                $proximo = 1;
                $this->pdo->prepare(
                    "INSERT INTO {$this->tableSeq} (usuario_id, ano, ultimo_numero) VALUES (:u, :a, 1)"
                )->execute([':u' => $uid, ':a' => $ano]);
            }
            $this->pdo->commit();
            return 'RDV-' . $ano . '-' . str_pad($proximo, 5, '0', STR_PAD_LEFT);
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            $this->logger->error('[RdvViagem::gerarCodigo] ' . $e->getMessage());
            return 'RDV-' . $ano . '-' . str_pad(rand(1000, 9999), 5, '0', STR_PAD_LEFT);
        }
    }

    // =========================================================================
    // CREATE
    // =========================================================================
    public function create(array $d): int|false
    {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO {$this->table}
                 (usuario_id, rota_id, codigo, nome, status,
                  periodo_inicio, periodo_fim, motivo,
                  cidade, estado, pais, valor_previsto, observacoes)
                 VALUES
                 (:usuario_id, :rota_id, :codigo, :nome, 'aberto',
                  :periodo_inicio, :periodo_fim, :motivo,
                  :cidade, :estado, :pais, :valor_previsto, :observacoes)"
            );
            $stmt->execute([
                ':usuario_id'     => (int)$d['usuario_id'],
                ':rota_id'        => !empty($d['rota_id'])        ? (int)$d['rota_id']        : null,
                ':codigo'         => $d['codigo'],
                ':nome'           => trim($d['nome']),
                ':periodo_inicio' => $d['periodo_inicio'],
                ':periodo_fim'    => $d['periodo_fim'],
                ':motivo'         => $d['motivo']         ?? null,
                ':cidade'         => $d['cidade']         ?? null,
                ':estado'         => $d['estado']         ?? null,
                ':pais'           => $d['pais']           ?? 'Brasil',
                ':valor_previsto' => (float)($d['valor_previsto'] ?? 0),
                ':observacoes'    => $d['observacoes']    ?? null,
            ]);
            return (int) $this->pdo->lastInsertId();
        } catch (\Throwable $e) {
            $this->logger->error('[RdvViagem::create] ' . $e->getMessage(), $d);
            return false;
        }
    }

    // =========================================================================
    // UPDATE
    // =========================================================================
    public function update(int $id, array $d): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE {$this->table} SET
                 rota_id        = :rota_id,
                 nome           = :nome,
                 periodo_inicio = :periodo_inicio,
                 periodo_fim    = :periodo_fim,
                 motivo         = :motivo,
                 cidade         = :cidade,
                 estado         = :estado,
                 pais           = :pais,
                 valor_previsto = :valor_previsto,
                 observacoes    = :observacoes
                 WHERE id = :id AND usuario_id = :uid"
            );
            $stmt->execute([
                ':rota_id'        => !empty($d['rota_id']) ? (int)$d['rota_id'] : null,
                ':nome'           => trim($d['nome']),
                ':periodo_inicio' => $d['periodo_inicio'],
                ':periodo_fim'    => $d['periodo_fim'],
                ':motivo'         => $d['motivo']         ?? null,
                ':cidade'         => $d['cidade']         ?? null,
                ':estado'         => $d['estado']         ?? null,
                ':pais'           => $d['pais']           ?? 'Brasil',
                ':valor_previsto' => (float)($d['valor_previsto'] ?? 0),
                ':observacoes'    => $d['observacoes']    ?? null,
                ':id'             => $id,
                ':uid'            => (int)$d['usuario_id'],
            ]);
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('[RdvViagem::update] ' . $e->getMessage(), ['id' => $id]);
            return false;
        }
    }

    // =========================================================================
    // STATUS
    // =========================================================================
    public function updateStatus(int $id, string $status): bool
    {
        try {
            $this->pdo->prepare(
                "UPDATE {$this->table} SET status = :s WHERE id = :id"
            )->execute([':s' => $status, ':id' => $id]);
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('[RdvViagem::updateStatus] ' . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // APROVAÇÃO
    // =========================================================================
    public function updateAprovacao(int $id, string $status, int $aprovadorId): bool
    {
        try {
            $this->pdo->prepare(
                "UPDATE {$this->table} SET
                 aprovacao_status = :s, aprovado_por = :ap, aprovado_em = NOW()
                 WHERE id = :id"
            )->execute([':s' => $status, ':ap' => $aprovadorId, ':id' => $id]);
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('[RdvViagem::updateAprovacao] ' . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // VINCULAR CONTA A PAGAR
    // =========================================================================
    public function vincularContaPagar(int $id, int $contaPagarId): bool
    {
        try {
            $this->pdo->prepare(
                "UPDATE {$this->table} SET conta_pagar_id = :cp WHERE id = :id"
            )->execute([':cp' => $contaPagarId, ':id' => $id]);
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('[RdvViagem::vincularContaPagar] ' . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // RECALCULAR VALOR REAL
    // =========================================================================
    public function recalcularValorReal(int $id): void
    {
        try {
            $this->pdo->prepare(
                "UPDATE {$this->table} v
                 SET v.valor_real = (
                     SELECT COALESCE(SUM(d.valor), 0)
                     FROM rdv_despesas d
                     WHERE d.viagem_id = v.id
                 )
                 WHERE v.id = :id"
            )->execute([':id' => $id]);
        } catch (\Throwable $e) {
            $this->logger->error('[RdvViagem::recalcularValorReal] ' . $e->getMessage());
        }
    }

    // =========================================================================
    // FIND BY ID
    // =========================================================================
    public function findById(int $id): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT v.*,
                    u.name AS vendedor_nome,
                    r.nome AS rota_nome, r.tipo AS rota_tipo,
                    DATEDIFF(v.periodo_fim, CURDATE()) AS dias_restantes,
                    DATEDIFF(v.periodo_fim, v.periodo_inicio) + 1 AS total_dias
             FROM {$this->table} v
             LEFT JOIN users u ON u.id = v.usuario_id
             LEFT JOIN rdv_rotas r ON r.id = v.rota_id
             WHERE v.id = :id"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(\PDO::FETCH_OBJ) ?: false;
    }

    // =========================================================================
    // LISTAGEM COM FILTROS
    // =========================================================================
    public function listar(int $uid, bool $isAdmin, array $filtros = []): array
    {
        $where  = [];
        $params = [];

        if (!$isAdmin) {
            $where[]          = 'v.usuario_id = :uid';
            $params[':uid']   = $uid;
        }
        if (!empty($filtros['status'])) {
            $where[]            = 'v.status = :status';
            $params[':status']  = $filtros['status'];
        }
        if (!empty($filtros['q'])) {
            $where[]         = '(v.nome LIKE :q OR v.codigo LIKE :q OR r.nome LIKE :q)';
            $params[':q']    = '%' . $filtros['q'] . '%';
        }
        if (!empty($filtros['rota_id'])) {
            $where[]              = 'v.rota_id = :rota_id';
            $params[':rota_id']   = (int)$filtros['rota_id'];
        }
        if (!empty($filtros['periodo_inicio'])) {
            $where[]                    = 'v.periodo_inicio >= :pi';
            $params[':pi']              = $filtros['periodo_inicio'];
        }
        if (!empty($filtros['periodo_fim'])) {
            $where[]                    = 'v.periodo_fim <= :pf';
            $params[':pf']              = $filtros['periodo_fim'];
        }
        if (!empty($filtros['vendedor_id'])) {
            $where[]                    = 'v.usuario_id = :vid';
            $params[':vid']             = (int)$filtros['vendedor_id'];
        }

        $sql = "SELECT v.*,
                       u.name AS vendedor_nome,
                       r.nome AS rota_nome,
                       (SELECT COUNT(*) FROM rdv_despesas d WHERE d.viagem_id = v.id) AS total_despesas
                FROM {$this->table} v
                LEFT JOIN users u ON u.id = v.usuario_id
                LEFT JOIN rdv_rotas r ON r.id = v.rota_id";

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY v.created_at DESC LIMIT 200';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    // =========================================================================
    // KPIs
    // =========================================================================
    public function kpis(int $uid, bool $isAdmin): object
    {
        $cond   = $isAdmin ? '' : 'WHERE usuario_id = :uid';
        $params = $isAdmin ? [] : [':uid' => $uid];

        $stmt = $this->pdo->prepare(
            "SELECT
               COUNT(*)                                                       AS total,
               SUM(status = 'aberto')                                         AS abertas,
               SUM(status = 'iniciado')                                       AS iniciadas,
               SUM(status = 'concluido')                                      AS concluidas,
               SUM(status = 'cancelado')                                      AS canceladas,
               SUM(CASE WHEN MONTH(periodo_inicio) = MONTH(CURDATE())
                        AND YEAR(periodo_inicio)  = YEAR(CURDATE())
                        THEN valor_real ELSE 0 END)                           AS valor_mes,
               SUM(valor_previsto)                                            AS total_previsto,
               SUM(valor_real)                                                AS total_real
             FROM {$this->table} {$cond}"
        );
        $stmt->execute($params);
        return $stmt->fetch(\PDO::FETCH_OBJ) ?: (object)[];
    }
}
