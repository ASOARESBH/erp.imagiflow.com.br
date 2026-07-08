<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Logger;

class RdvRota
{
    private \PDO   $pdo;
    private Logger $logger;

    public function __construct()
    {
        $this->pdo    = Database::getInstance();
        $this->logger = new Logger();
    }

    // =========================================================================
    // CRUD DE ROTAS
    // =========================================================================
    public function create(array $d): int|false
    {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO rdv_rotas (usuario_id, nome, descricao, tipo, regiao, estado)
                 VALUES (:uid, :nome, :descricao, :tipo, :regiao, :estado)"
            );
            $stmt->execute([
                ':uid'      => (int)$d['usuario_id'],
                ':nome'     => trim($d['nome']),
                ':descricao'=> $d['descricao'] ?? null,
                ':tipo'     => $d['tipo']      ?? 'padrao',
                ':regiao'   => $d['regiao']    ?? null,
                ':estado'   => $d['estado']    ?? null,
            ]);
            return (int) $this->pdo->lastInsertId();
        } catch (\Throwable $e) {
            $this->logger->error('[RdvRota::create] ' . $e->getMessage(), $d);
            return false;
        }
    }

    public function update(int $id, array $d): bool
    {
        try {
            $this->pdo->prepare(
                "UPDATE rdv_rotas SET
                 nome = :nome, descricao = :descricao, tipo = :tipo,
                 regiao = :regiao, estado = :estado
                 WHERE id = :id"
            )->execute([
                ':nome'      => trim($d['nome']),
                ':descricao' => $d['descricao'] ?? null,
                ':tipo'      => $d['tipo']      ?? 'padrao',
                ':regiao'    => $d['regiao']    ?? null,
                ':estado'    => $d['estado']    ?? null,
                ':id'        => $id,
            ]);
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('[RdvRota::update] ' . $e->getMessage());
            return false;
        }
    }

    public function delete(int $id): bool
    {
        try {
            $this->pdo->prepare("UPDATE rdv_rotas SET ativo = 0 WHERE id = :id")
                      ->execute([':id' => $id]);
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('[RdvRota::delete] ' . $e->getMessage());
            return false;
        }
    }

    public function findById(int $id): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT r.*,
                    (SELECT COUNT(*) FROM rdv_rota_clientes rc WHERE rc.rota_id = r.id) AS total_clientes,
                    (SELECT COUNT(*) FROM rdv_viagens v WHERE v.rota_id = r.id) AS total_viagens
             FROM rdv_rotas r WHERE r.id = :id"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(\PDO::FETCH_OBJ) ?: false;
    }

    public function listar(int $uid, bool $isAdmin): array
    {
        $cond   = $isAdmin ? 'WHERE r.ativo = 1' : 'WHERE r.ativo = 1 AND r.usuario_id = :uid';
        $params = $isAdmin ? [] : [':uid' => $uid];
        $stmt   = $this->pdo->prepare(
            "SELECT r.*,
                    (SELECT COUNT(*) FROM rdv_rota_clientes rc WHERE rc.rota_id = r.id) AS total_clientes,
                    (SELECT COUNT(*) FROM rdv_viagens v WHERE v.rota_id = r.id) AS total_viagens
             FROM rdv_rotas r {$cond} ORDER BY r.nome ASC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    public function listarParaSelect(int $uid, bool $isAdmin): array
    {
        $cond   = $isAdmin ? 'WHERE ativo = 1' : 'WHERE ativo = 1 AND usuario_id = :uid';
        $params = $isAdmin ? [] : [':uid' => $uid];
        $stmt   = $this->pdo->prepare(
            "SELECT id, nome, tipo FROM rdv_rotas {$cond} ORDER BY nome ASC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    // =========================================================================
    // CLIENTES/LEADS DA ROTA
    // =========================================================================
    public function getClientes(int $rotaId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT rc.*,
                    COALESCE(c.nome_fantasia, c.razao_social) AS cliente_nome,
                    c.cpf_cnpj AS cliente_doc,
                    c.cidade AS cliente_cidade, c.estado AS cliente_estado,
                    l.nome_lead AS lead_nome, l.razao_social AS lead_empresa,
                    o.titulo_oportunidade AS oportunidade_titulo
             FROM rdv_rota_clientes rc
             LEFT JOIN clientes c ON c.id = rc.cliente_id
             LEFT JOIN crm_leads l ON l.id = rc.lead_id
             LEFT JOIN crm_oportunidades o ON o.id = rc.oportunidade_id
             WHERE rc.rota_id = :rid
             ORDER BY rc.ordem ASC, rc.id ASC"
        );
        $stmt->execute([':rid' => $rotaId]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    public function addCliente(int $rotaId, array $d): int|false
    {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO rdv_rota_clientes
                 (rota_id, cliente_id, lead_id, oportunidade_id, ordem, observacoes)
                 VALUES (:rid, :cid, :lid, :oid, :ordem, :obs)"
            );
            $stmt->execute([
                ':rid'   => $rotaId,
                ':cid'   => !empty($d['cliente_id'])      ? (int)$d['cliente_id']      : null,
                ':lid'   => !empty($d['lead_id'])         ? (int)$d['lead_id']         : null,
                ':oid'   => !empty($d['oportunidade_id']) ? (int)$d['oportunidade_id'] : null,
                ':ordem' => (int)($d['ordem'] ?? 0),
                ':obs'   => $d['observacoes'] ?? null,
            ]);
            return (int) $this->pdo->lastInsertId();
        } catch (\Throwable $e) {
            $this->logger->error('[RdvRota::addCliente] ' . $e->getMessage(), $d);
            return false;
        }
    }

    public function removeCliente(int $itemId, int $rotaId): bool
    {
        try {
            $this->pdo->prepare(
                "DELETE FROM rdv_rota_clientes WHERE id = :id AND rota_id = :rid"
            )->execute([':id' => $itemId, ':rid' => $rotaId]);
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('[RdvRota::removeCliente] ' . $e->getMessage());
            return false;
        }
    }
}
