<?php

namespace App\Services;

use App\Core\Audit\AuditLogger;
use App\Core\Auth;
use App\Models\PasswordResetToken;
use App\Models\Plano;
use App\Models\Tenant;
use App\Models\TenantImpersonationLog;
use App\Models\User;
use PDO;
use RuntimeException;

/**
 * Regras de negócio do control-plane SaaS.
 * Todas as mutações são executadas com transação e auditadas.
 */
class SaasAdminService
{
    private Tenant $tenantModel;
    private User $userModel;
    private PasswordResetToken $resetTokenModel;
    private Plano $planoModel;
    private TenantImpersonationLog $impersonationLog;
    private PDO $pdo;

    public function __construct()
    {
        $this->tenantModel = new Tenant();
        $this->userModel = new User();
        $this->resetTokenModel = new PasswordResetToken();
        $this->planoModel = new Plano();
        $this->impersonationLog = new TenantImpersonationLog();
        $this->pdo = $this->tenantModel->getPdo();
    }

    /**
     * Provisiona empresa, tenant e usuário master sem senha em texto puro.
     * Retorna o tenant e o link de convite, que deve ser enviado por e-mail pelo controller.
     */
    public function provisionCompany(array $company, array $master, int $saasAdminId): array
    {
        $cnpj = preg_replace('/\D/', '', (string) ($company['cnpj'] ?? ''));
        $email = strtolower(trim((string) ($master['email'] ?? '')));
        $slug = strtolower(trim((string) ($company['slug'] ?? '')));

        if (strlen($cnpj) !== 14 || !$this->isValidCnpj($cnpj)) {
            throw new RuntimeException('CNPJ inválido.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('E-mail do usuário master inválido.');
        }
        if (!preg_match('/^[a-z0-9-]{3,120}$/', $slug)) {
            throw new RuntimeException('Slug inválido. Use letras minúsculas, números e hífen.');
        }
        if ($this->tenantModel->cnpjExists($cnpj)) {
            throw new RuntimeException('Já existe uma empresa cadastrada com este CNPJ.');
        }
        if ($this->tenantModel->slugExists($slug)) {
            throw new RuntimeException('Este slug já está em uso.');
        }
        if ($this->findGlobalUserByEmail($email)) {
            throw new RuntimeException('Já existe um usuário global com este e-mail. Use outro e-mail para o administrador master.');
        }
        $plan = $this->planoModel->findById((int) ($company['plano_id'] ?? 0));
        if (!$plan || $plan->status !== 'ativo') {
            throw new RuntimeException('Selecione um plano SaaS ativo.');
        }

        $this->pdo->beginTransaction();
        try {
            $tenantId = $this->tenantModel->createSaas([
                'name' => $company['nome_fantasia'],
                'slug' => $slug,
                'domain' => $company['domain'] ?: null,
                'subdomain' => $company['subdomain'] ?: null,
                'status' => 'active',
                'email' => $company['email'] ?: null,
                'phone' => $company['phone'] ?: null,
                'cnpj' => $cnpj,
                'razao_social' => $company['razao_social'],
                'nome_fantasia' => $company['nome_fantasia'],
                'endereco' => $company['endereco'] ?: null,
                'numero' => $company['numero'] ?: null,
                'complemento' => $company['complemento'] ?: null,
                'bairro' => $company['bairro'] ?: null,
                'cidade' => $company['cidade'] ?: null,
                'estado' => $company['estado'] ?: null,
                'cep' => $company['cep'] ?: null,
                'plano_id' => $company['plano_id'],
                'plano_iniciado_em' => date('Y-m-d H:i:s'),
                'trial_ends_at' => $company['trial_ends_at'] ?: null,
                'billing_email' => $company['billing_email'] ?: $email,
                'created_by_saas_admin_id' => $saasAdminId,
                'notes' => $company['notes'] ?: null,
            ]);

            $temporaryHash = Auth::hashPassword(bin2hex(random_bytes(32)));
            $masterUserId = $this->createGlobalMasterUser([
                'name' => $master['name'],
                'email' => $email,
                'password' => $temporaryHash,
                'role' => 'superadmin',
            ], $tenantId);

            if (!$this->tenantModel->setMasterUser($tenantId, $masterUserId)) {
                throw new RuntimeException('Não foi possível vincular o usuário master ao tenant.');
            }

            // Token armazenado somente como hash; é usado uma única vez no link de definição de senha.
            $token = bin2hex(random_bytes(32));
            $this->createPasswordResetTokenForTenant($masterUserId, $tenantId, $token);

            $this->pdo->commit();

            AuditLogger::log('saas_company_provisioned', [
                'tenant_id' => $tenantId,
                'master_user_id' => $masterUserId,
                'created_by_saas_admin_id' => $saasAdminId,
                'cnpj' => $cnpj,
                'plan_id' => $company['plano_id'],
            ]);

            return [
                'tenant_id' => $tenantId,
                'master_user_id' => $masterUserId,
                'invite_token' => $token,
            ];
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            AuditLogger::log('saas_company_provision_failed', [
                'created_by_saas_admin_id' => $saasAdminId,
                'cnpj' => $cnpj,
                'error' => $exception->getMessage(),
            ]);
            throw $exception;
        }
    }

    public function updateCompany(int $tenantId, array $company): bool
    {
        $plan = $this->planoModel->findById((int) ($company['plano_id'] ?? 0));
        if (!$plan || $plan->status !== 'ativo') {
            throw new RuntimeException('Selecione um plano SaaS ativo.');
        }
        if ($this->tenantModel->cnpjExists($company['cnpj'], $tenantId)) {
            throw new RuntimeException('Já existe outra empresa com este CNPJ.');
        }
        if ($this->tenantModel->slugExists($company['slug'], $tenantId)) {
            throw new RuntimeException('Já existe outra empresa com este slug.');
        }

        $updated = $this->tenantModel->updateSaas($tenantId, $company);
        if ($updated) {
            AuditLogger::log('saas_company_updated', ['tenant_id' => $tenantId]);
        }

        return $updated;
    }

    public function changeCompanyStatus(int $tenantId, string $status, int $saasAdminId): bool
    {
        if (!in_array($status, ['active', 'inactive', 'suspended'], true)) {
            throw new RuntimeException('Status de empresa inválido.');
        }
        if ($tenantId === (int) ($_ENV['SAAS_CONTROL_TENANT_ID'] ?? 0)) {
            throw new RuntimeException('O tenant de controle não pode ser suspenso por este fluxo.');
        }

        $updated = $this->tenantModel->updateStatus($tenantId, $status);
        if ($updated) {
            AuditLogger::log('saas_company_status_changed', [
                'tenant_id' => $tenantId,
                'status' => $status,
                'changed_by_saas_admin_id' => $saasAdminId,
            ]);
        }

        return $updated;
    }

    /**
     * Gera links de uso único em vez de confiar no cliente para definir tenant.
     */
    public function startImpersonation(int $targetTenantId, int $saasAdminId, string $reason = ''): array
    {
        $tenant = $this->tenantModel->findActiveById($targetTenantId);
        if (!$tenant || (int) $targetTenantId === (int) ($_ENV['SAAS_CONTROL_TENANT_ID'] ?? 0)) {
            throw new RuntimeException('Tenant de destino inválido para impersonação.');
        }
        $master = $this->findGlobalUserById((int) ($tenant->master_user_id ?? 0));
        if (!$master) {
            throw new RuntimeException('A empresa não possui usuário master ativo para impersonação.');
        }

        $handoffToken = bin2hex(random_bytes(32));
        $returnToken = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        $logId = $this->impersonationLog->create([
            'saas_admin_user_id' => $saasAdminId,
            'target_tenant_id' => $targetTenantId,
            'target_user_id' => (int) $master->id,
            'reason' => trim($reason),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            'handoff_token_hash' => hash('sha256', $handoffToken),
            'handoff_expires_at' => $expires,
            'return_token_hash' => hash('sha256', $returnToken),
            'return_expires_at' => $expires,
        ]);

        AuditLogger::log('saas_impersonation_started', [
            'log_id' => $logId,
            'target_tenant_id' => $targetTenantId,
            'target_user_id' => $master->id,
            'saas_admin_user_id' => $saasAdminId,
            'reason' => trim($reason),
        ]);

        return [
            'log_id' => $logId,
            'handoff_token' => $handoffToken,
            'return_token' => $returnToken,
            'target_tenant' => $tenant,
        ];
    }

    public function consumeImpersonationHandoff(string $token): object|false
    {
        return $this->impersonationLog->consumeHandoff(hash('sha256', $token));
    }

    public function returnFromImpersonation(string $token): object|false
    {
        return $this->impersonationLog->consumeReturn(hash('sha256', $token));
    }

    public function listRecentImpersonations(): array
    {
        return $this->impersonationLog->listRecent();
    }

    private function createGlobalMasterUser(array $data, int $tenantId): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (name, email, password, role, status, created_at, updated_at)
             VALUES (:name, :email, :password, :role, 'ativo', NOW(), NOW())"
        );
        $stmt->execute($data);
        $userId = (int) $this->pdo->lastInsertId();

        $link = $this->pdo->prepare(
            "INSERT INTO user_tenants (user_id, tenant_id, role, status, is_default, created_at, updated_at)
             VALUES (:user_id, :tenant_id, 'superadmin', 'active', 1, NOW(), NOW())"
        );
        $link->execute([':user_id' => $userId, ':tenant_id' => $tenantId]);

        return $userId;
    }

    private function createPasswordResetTokenForTenant(int $userId, int $tenantId, string $token): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO password_reset_tokens (user_id, tenant_id, token_hash, expires_at)
             VALUES (:user_id, :tenant_id, :token_hash, :expires_at)"
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':tenant_id' => $tenantId,
            ':token_hash' => hash('sha256', $token),
            ':expires_at' => date('Y-m-d H:i:s', strtotime('+60 minutes')),
        ]);
    }

    private function findGlobalUserByEmail(string $email): object|false
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);

        return $stmt->fetch(PDO::FETCH_OBJ) ?: false;
    }

    private function findGlobalUserById(int $id): object|false
    {
        if ($id <= 0) {
            return false;
        }
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = :id AND status = 'ativo' LIMIT 1");
        $stmt->execute([':id' => $id]);

        return $stmt->fetch(PDO::FETCH_OBJ) ?: false;
    }

    private function isValidCnpj(string $cnpj): bool
    {
        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }
        $weights = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        foreach ($weights as $index => $weight) {
            $sum += (int) $cnpj[$index] * $weight;
        }
        $first = $sum % 11 < 2 ? 0 : 11 - ($sum % 11);
        if ((int) $cnpj[12] !== $first) {
            return false;
        }
        $secondWeights = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        foreach ($secondWeights as $index => $weight) {
            $sum += (int) $cnpj[$index] * $weight;
        }
        $second = $sum % 11 < 2 ? 0 : 11 - ($sum % 11);

        return (int) $cnpj[13] === $second;
    }
}
