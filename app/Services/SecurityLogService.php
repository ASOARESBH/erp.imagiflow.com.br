<?php

namespace App\Services;

use App\Core\Database;

/**
 * Log de segurança do fluxo de 2FA (tabela security_two_factor_logs).
 * Nunca falha o fluxo principal — erros de log são apenas registrados via error_log().
 */
class SecurityLogService
{
    /**
     * @param string      $action  code_sent|verify_success|verify_failed|resend|locked|enabled|disabled
     * @param int|null    $userId
     * @param string|null $email
     * @param array       $context ip, user_agent, tentativas (demais chaves são ignoradas)
     */
    public function log(string $action, ?int $userId, ?string $email, array $context = []): void
    {
        try {
            $pdo = Database::getInstance();

            $ip        = $context['ip']         ?? ($_SERVER['REMOTE_ADDR']     ?? null);
            $userAgent = $context['user_agent']  ?? ($_SERVER['HTTP_USER_AGENT'] ?? null);
            [$os, $browser] = self::parseUserAgent((string) $userAgent);

            $stmt = $pdo->prepare(
                "INSERT INTO security_two_factor_logs
                 (user_id, email, ip_address, user_agent, os, browser, action, attempts, created_at)
                 VALUES (:user_id, :email, :ip, :ua, :os, :browser, :action, :attempts, NOW())"
            );
            $stmt->execute([
                ':user_id'  => $userId,
                ':email'    => $email,
                ':ip'       => $ip ? substr((string) $ip, 0, 45) : null,
                ':ua'       => $userAgent ? substr((string) $userAgent, 0, 255) : null,
                ':os'       => $os,
                ':browser'  => $browser,
                ':action'   => $action,
                ':attempts' => (int) ($context['tentativas'] ?? 0),
            ]);
        } catch (\Throwable $e) {
            error_log('[SecurityLogService::log] ' . $e->getMessage());
        }
    }

    /**
     * Detecção heurística simples de SO/navegador a partir do User-Agent.
     * Não é uma biblioteca completa de parsing — suficiente para fins de auditoria.
     */
    private static function parseUserAgent(string $ua): array
    {
        $os = 'Desconhecido';
        $browser = 'Desconhecido';

        if (preg_match('/windows/i', $ua)) {
            $os = 'Windows';
        } elseif (preg_match('/android/i', $ua)) {
            $os = 'Android';
        } elseif (preg_match('/iphone|ipad|ipod/i', $ua)) {
            $os = 'iOS';
        } elseif (preg_match('/mac os/i', $ua)) {
            $os = 'macOS';
        } elseif (preg_match('/linux/i', $ua)) {
            $os = 'Linux';
        }

        if (preg_match('/edg\//i', $ua)) {
            $browser = 'Edge';
        } elseif (preg_match('/opr\//i', $ua)) {
            $browser = 'Opera';
        } elseif (preg_match('/chrome\//i', $ua)) {
            $browser = 'Chrome';
        } elseif (preg_match('/firefox\//i', $ua)) {
            $browser = 'Firefox';
        } elseif (preg_match('/safari\//i', $ua)) {
            $browser = 'Safari';
        }

        return [$os, $browser];
    }
}
