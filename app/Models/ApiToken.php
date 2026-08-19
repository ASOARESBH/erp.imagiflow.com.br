<?php

namespace App\Models;

use App\Core\Logger;
use App\Core\Model;
use PDO;

class ApiToken extends Model
{
    private const DEFAULT_TTL_DAYS = 30;

    /**
     * Cria um token opaco para um dispositivo. Apenas o hash é persistido.
     *
     * @return array{id:int, token:string, expires_at:string}|false
     */
    public function issue(int $tenantId, int $userId, ?string $deviceName, ?string $devicePlatform): array|false
    {
        $plainToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $plainToken);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::DEFAULT_TTL_DAYS . ' days'));

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO api_tokens
                    (tenant_id, user_id, token_hash, device_name, device_platform, last_used_at, expires_at)
                 VALUES
                    (:tenant_id, :user_id, :token_hash, :device_name, :device_platform, NOW(), :expires_at)'
            );
            $stmt->execute([
                ':tenant_id' => $tenantId,
                ':user_id' => $userId,
                ':token_hash' => $tokenHash,
                ':device_name' => $deviceName !== '' ? $deviceName : null,
                ':device_platform' => $devicePlatform,
                ':expires_at' => $expiresAt,
            ]);

            return [
                'id' => (int) $this->pdo->lastInsertId(),
                'token' => $plainToken,
                'expires_at' => $expiresAt,
            ];
        } catch (\Throwable $exception) {
            (new Logger())->error('Falha ao emitir token da API mobile', [
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'error' => $exception->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Retorna um token ainda válido com o usuário e o papel no tenant associado.
     * Não toca o token aqui para preservar o método como consulta determinística.
     */
    public function findActiveByPlainToken(string $plainToken): object|false
    {
        if ($plainToken === '' || strlen($plainToken) < 32) {
            return false;
        }

        $stmt = $this->pdo->prepare(
            'SELECT at.*, u.name AS user_name, u.email AS user_email, u.locale AS user_locale,
                    ut.role AS tenant_role
             FROM api_tokens at
             INNER JOIN users u ON u.id = at.user_id
             INNER JOIN user_tenants ut
                     ON ut.user_id = at.user_id
                    AND ut.tenant_id = at.tenant_id
                    AND ut.status = \'active\'
             INNER JOIN tenants t ON t.id = at.tenant_id AND t.status = \'active\'
             WHERE at.token_hash = :token_hash
               AND at.revoked_at IS NULL
               AND (at.expires_at IS NULL OR at.expires_at >= NOW())
             LIMIT 1'
        );
        $stmt->execute([':token_hash' => hash('sha256', $plainToken)]);

        return $stmt->fetch(PDO::FETCH_OBJ) ?: false;
    }

    public function touch(int $tokenId): bool
    {
        $stmt = $this->pdo->prepare('UPDATE api_tokens SET last_used_at = NOW() WHERE id = :id AND revoked_at IS NULL');
        return $stmt->execute([':id' => $tokenId]);
    }

    public function revoke(int $tokenId, int $tenantId, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE api_tokens
             SET revoked_at = NOW()
             WHERE id = :id AND tenant_id = :tenant_id AND user_id = :user_id AND revoked_at IS NULL'
        );
        return $stmt->execute([
            ':id' => $tokenId,
            ':tenant_id' => $tenantId,
            ':user_id' => $userId,
        ]);
    }

    public function revokeCurrent(int $tokenId, int $tenantId, int $userId): bool
    {
        return $this->revoke($tokenId, $tenantId, $userId);
    }

    public function listActiveForUser(int $tenantId, int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, device_name, device_platform, push_token, last_used_at, expires_at, created_at
             FROM api_tokens
             WHERE tenant_id = :tenant_id
               AND user_id = :user_id
               AND revoked_at IS NULL
               AND (expires_at IS NULL OR expires_at >= NOW())
             ORDER BY last_used_at DESC, created_at DESC'
        );
        $stmt->execute([':tenant_id' => $tenantId, ':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function updatePushToken(int $tokenId, int $tenantId, int $userId, ?string $pushToken): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE api_tokens
             SET push_token = :push_token, last_used_at = NOW()
             WHERE id = :id
               AND tenant_id = :tenant_id
               AND user_id = :user_id
               AND revoked_at IS NULL'
        );
        return $stmt->execute([
            ':push_token' => $pushToken !== '' ? $pushToken : null,
            ':id' => $tokenId,
            ':tenant_id' => $tenantId,
            ':user_id' => $userId,
        ]);
    }
}
