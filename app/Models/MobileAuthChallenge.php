<?php

namespace App\Models;

use App\Core\Logger;
use App\Core\Model;
use PDO;

class MobileAuthChallenge extends Model
{
    private const TTL_MINUTES = 5;

    /** @return array{token:string, expires_at:string}|false */
    public function create(int $tenantId, int $userId, ?string $deviceName, ?string $devicePlatform): array|false
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::TTL_MINUTES . ' minutes'));

        try {
            $this->pdo->beginTransaction();

            $invalidate = $this->pdo->prepare(
                'UPDATE mobile_auth_challenges
                 SET consumed_at = NOW()
                 WHERE tenant_id = :tenant_id
                   AND user_id = :user_id
                   AND consumed_at IS NULL'
            );
            $invalidate->execute([':tenant_id' => $tenantId, ':user_id' => $userId]);

            $stmt = $this->pdo->prepare(
                'INSERT INTO mobile_auth_challenges
                    (tenant_id, user_id, challenge_hash, device_name, device_platform, expires_at)
                 VALUES
                    (:tenant_id, :user_id, :challenge_hash, :device_name, :device_platform, :expires_at)'
            );
            $stmt->execute([
                ':tenant_id' => $tenantId,
                ':user_id' => $userId,
                ':challenge_hash' => hash('sha256', $token),
                ':device_name' => $deviceName !== '' ? $deviceName : null,
                ':device_platform' => $devicePlatform,
                ':expires_at' => $expiresAt,
            ]);

            $this->pdo->commit();
            return ['token' => $token, 'expires_at' => $expiresAt];
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            (new Logger())->error('Falha ao criar desafio mobile de 2FA', [
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'error' => $exception->getMessage(),
            ]);
            return false;
        }
    }

    public function findPendingByPlainToken(string $token): object|false
    {
        if ($token === '' || strlen($token) < 32) {
            return false;
        }

        $stmt = $this->pdo->prepare(
            'SELECT *
             FROM mobile_auth_challenges
             WHERE challenge_hash = :challenge_hash
               AND consumed_at IS NULL
               AND expires_at >= NOW()
             LIMIT 1'
        );
        $stmt->execute([':challenge_hash' => hash('sha256', $token)]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: false;
    }

    public function consume(int $challengeId): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE mobile_auth_challenges
             SET consumed_at = NOW()
             WHERE id = :id AND consumed_at IS NULL AND expires_at >= NOW()'
        );
        $stmt->execute([':id' => $challengeId]);
        return $stmt->rowCount() === 1;
    }

    public function deviceData(object $challenge): array
    {
        return [
            'device_name' => $challenge->device_name ?? null,
            'device_platform' => $challenge->device_platform ?? null,
        ];
    }
}
