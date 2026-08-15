<?php

namespace App\Services;

use App\Core\Audit\AuditLogger;
use App\Core\Auth;
use App\Core\TenantContext;
use App\Models\Tenant;
use App\Models\TenantImpersonationLog;
use App\Models\User;
use RuntimeException;

/**
 * Impersonação segura por handoff entre hosts.
 * O tenant continua sendo definido somente pelo HTTP_HOST de cada requisição.
 */
class SaasImpersonationService
{
    private Tenant $tenantModel;
    private TenantImpersonationLog $logModel;
    private User $userModel;

    public function __construct()
    {
        $this->tenantModel = new Tenant();
        $this->logModel = new TenantImpersonationLog();
        $this->userModel = new User();
    }

    public function createHandoff(int $targetTenantId, int $saasAdminUserId, string $reason = ''): array
    {
        if (isset($_SESSION['impersonation_origin'])) {
            throw new RuntimeException('Encerre a impersonação atual antes de iniciar outra.');
        }

        $tenant = $this->tenantModel->findActiveById($targetTenantId);
        $controlTenantId = (int) ($_ENV['SAAS_CONTROL_TENANT_ID'] ?? 0);
        if (!$tenant || $targetTenantId === $controlTenantId) {
            throw new RuntimeException('Tenant inválido para impersonação.');
        }

        $masterUserId = (int) ($tenant->master_user_id ?? 0);
        if ($masterUserId <= 0) {
            $masterUserId = $this->findMasterUserId($targetTenantId);
        }
        if ($masterUserId <= 0) {
            throw new RuntimeException('A empresa não possui usuário master ativo.');
        }

        $entryToken = bin2hex(random_bytes(32));
        $returnToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        $logId = $this->logModel->create([
            'saas_admin_user_id' => $saasAdminUserId,
            'target_tenant_id' => $targetTenantId,
            'target_user_id' => $masterUserId,
            'reason' => trim($reason),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            'handoff_token_hash' => hash('sha256', $entryToken),
            'handoff_expires_at' => $expiresAt,
            'return_token_hash' => hash('sha256', $returnToken),
            'return_expires_at' => $expiresAt,
        ]);

        AuditLogger::log('saas_impersonation_started', [
            'log_id' => $logId,
            'saas_admin_user_id' => $saasAdminUserId,
            'target_tenant_id' => $targetTenantId,
            'target_user_id' => $masterUserId,
            'reason' => trim($reason),
        ]);

        return [
            'log_id' => $logId,
            'target_tenant' => $tenant,
            'entry_token' => $entryToken,
            'return_token' => $returnToken,
        ];
    }

    public function enterTargetTenant(string $entryToken, string $returnToken): void
    {
        $log = $this->logModel->consumeHandoff(hash('sha256', $entryToken));
        if (!$log || !TenantContext::matches((int) $log->target_tenant_id)
            || !$this->logModel->returnTokenMatchesLog((int) $log->id, hash('sha256', $returnToken))) {
            throw new RuntimeException('Link de impersonação inválido, expirado ou direcionado ao tenant incorreto.');
        }

        $user = $this->userModel->findById((int) $log->target_user_id);
        if (!$user) {
            throw new RuntimeException('O usuário master do tenant não está disponível.');
        }

        Auth::loginAsUser($user);
        $_SESSION['impersonation_origin'] = [
            'log_id' => (int) $log->id,
            'saas_admin_user_id' => (int) $log->saas_admin_user_id,
            'target_tenant_id' => (int) $log->target_tenant_id,
            'return_token' => $returnToken,
            'started_at' => time(),
        ];

    }

    public function finalizeReturn(string $returnToken): object|false
    {
        $log = $this->logModel->consumeReturn(hash('sha256', $returnToken));
        if ($log) {
            AuditLogger::log('saas_impersonation_ended', [
                'log_id' => $log->id,
                'saas_admin_user_id' => $log->saas_admin_user_id,
                'target_tenant_id' => $log->target_tenant_id,
                'reason' => 'manual',
            ]);
        }

        return $log;
    }

    public function closeForTimeout(int $logId): void
    {
        $this->logModel->closeExpired($logId);
        AuditLogger::log('saas_impersonation_ended', [
            'log_id' => $logId,
            'reason' => 'timeout',
        ]);
    }

    private function findMasterUserId(int $tenantId): int
    {
        $stmt = $this->tenantModel->getPdo()->prepare(
            "SELECT user_id FROM user_tenants
             WHERE tenant_id = :tenant_id
               AND role = 'superadmin'
               AND status = 'active'
             ORDER BY is_default DESC, id ASC
             LIMIT 1"
        );
        $stmt->execute([':tenant_id' => $tenantId]);

        return (int) $stmt->fetchColumn();
    }
}
