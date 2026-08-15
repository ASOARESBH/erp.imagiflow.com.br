<?php

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Registro global e auditável das sessões de impersonação SaaS.
 */
class TenantImpersonationLog extends Model
{
    protected string $table = 'tenant_impersonation_logs';

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->table}
                (saas_admin_user_id, target_tenant_id, target_user_id, reason, ip_address, user_agent,
                 handoff_token_hash, handoff_expires_at, return_token_hash, return_expires_at, started_at)
             VALUES
                (:saas_admin_user_id, :target_tenant_id, :target_user_id, :reason, :ip_address, :user_agent,
                 :handoff_token_hash, :handoff_expires_at, :return_token_hash, :return_expires_at, NOW())"
        );
        $stmt->execute([
            ':saas_admin_user_id' => $data['saas_admin_user_id'],
            ':target_tenant_id' => $data['target_tenant_id'],
            ':target_user_id' => $data['target_user_id'],
            ':reason' => $data['reason'] ?: null,
            ':ip_address' => $data['ip_address'] ?: null,
            ':user_agent' => $data['user_agent'] ?: null,
            ':handoff_token_hash' => $data['handoff_token_hash'],
            ':handoff_expires_at' => $data['handoff_expires_at'],
            ':return_token_hash' => $data['return_token_hash'],
            ':return_expires_at' => $data['return_expires_at'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function consumeHandoff(string $tokenHash): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table}
             WHERE handoff_token_hash = :token_hash
               AND handoff_used_at IS NULL
               AND handoff_expires_at > NOW()
               AND ended_at IS NULL
             LIMIT 1"
        );
        $stmt->execute([':token_hash' => $tokenHash]);
        $log = $stmt->fetch(PDO::FETCH_OBJ) ?: false;
        if (!$log) {
            return false;
        }

        $update = $this->pdo->prepare(
            "UPDATE {$this->table}
             SET handoff_used_at = NOW()
             WHERE id = :id AND handoff_used_at IS NULL"
        );
        if (!$update->execute([':id' => $log->id]) || $update->rowCount() !== 1) {
            return false;
        }

        return $log;
    }

    public function consumeReturn(string $tokenHash): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table}
             WHERE return_token_hash = :token_hash
               AND return_used_at IS NULL
               AND return_expires_at > NOW()
               AND ended_at IS NULL
             LIMIT 1"
        );
        $stmt->execute([':token_hash' => $tokenHash]);
        $log = $stmt->fetch(PDO::FETCH_OBJ) ?: false;
        if (!$log) {
            return false;
        }

        $update = $this->pdo->prepare(
            "UPDATE {$this->table}
             SET return_used_at = NOW(), ended_at = NOW(), end_reason = 'manual'
             WHERE id = :id AND return_used_at IS NULL"
        );
        if (!$update->execute([':id' => $log->id]) || $update->rowCount() !== 1) {
            return false;
        }

        return $log;
    }

    public function returnTokenMatchesLog(int $logId, string $tokenHash): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT 1 FROM {$this->table}
             WHERE id = :id
               AND return_token_hash = :token_hash
               AND ended_at IS NULL
               AND return_expires_at > NOW()
             LIMIT 1"
        );
        $stmt->execute([':id' => $logId, ':token_hash' => $tokenHash]);

        return (bool) $stmt->fetchColumn();
    }

    public function closeExpired(int $logId): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table}
             SET ended_at = NOW(), end_reason = 'timeout'
             WHERE id = :id AND ended_at IS NULL"
        );

        return $stmt->execute([':id' => $logId]);
    }

    public function listRecent(int $limit = 100): array
    {
        $limit = max(1, min($limit, 500));
        $stmt = $this->pdo->query(
            "SELECT l.*, t.name AS target_tenant_name, u.name AS saas_admin_name
             FROM {$this->table} l
             INNER JOIN tenants t ON t.id = l.target_tenant_id
             LEFT JOIN users u ON u.id = l.saas_admin_user_id
             ORDER BY l.started_at DESC
             LIMIT {$limit}"
        );

        return $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];
    }
}
