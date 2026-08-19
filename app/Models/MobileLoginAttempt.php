<?php

namespace App\Models;

use App\Core\Logger;
use App\Core\Model;
use PDO;

class MobileLoginAttempt extends Model
{
    private const WINDOW_MINUTES = 15;
    private const MAX_FAILURES = 5;

    public function isBlocked(int $tenantId, string $email, string $ipAddress): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM mobile_login_attempts
             WHERE tenant_id = :tenant_id
               AND email_hash = :email_hash
               AND ip_address = :ip_address
               AND success = 0
               AND attempted_at >= DATE_SUB(NOW(), INTERVAL ' . self::WINDOW_MINUTES . ' MINUTE)'
        );
        $stmt->execute([
            ':tenant_id' => $tenantId,
            ':email_hash' => $this->emailHash($email),
            ':ip_address' => $this->normalizeIp($ipAddress),
        ]);
        return (int) $stmt->fetchColumn() >= self::MAX_FAILURES;
    }

    public function register(int $tenantId, string $email, string $ipAddress, bool $success): void
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO mobile_login_attempts (tenant_id, email_hash, ip_address, success)
                 VALUES (:tenant_id, :email_hash, :ip_address, :success)'
            );
            $stmt->execute([
                ':tenant_id' => $tenantId,
                ':email_hash' => $this->emailHash($email),
                ':ip_address' => $this->normalizeIp($ipAddress),
                ':success' => $success ? 1 : 0,
            ]);
        } catch (\Throwable $exception) {
            (new Logger())->warning('Falha ao registrar tentativa de login mobile', [
                'tenant_id' => $tenantId,
                'ip' => $this->normalizeIp($ipAddress),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function retryAfterSeconds(): int
    {
        return self::WINDOW_MINUTES * 60;
    }

    private function emailHash(string $email): string
    {
        return hash('sha256', strtolower(trim($email)));
    }

    private function normalizeIp(string $ipAddress): string
    {
        return substr(trim($ipAddress) !== '' ? trim($ipAddress) : 'unknown', 0, 45);
    }
}
