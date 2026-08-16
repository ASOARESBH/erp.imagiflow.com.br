<?php

namespace App\Core;

use App\Core\Audit\AuditLogger;
use App\Core\Permission;
use App\Core\TenantContext;
use LogicException;

class Auth
{
    /**
     * Gera um hash de senha seguro.
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID);
    }

    /**
     * Verifica se uma senha corresponde a um hash.
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Tenta realizar o login do usuário no tenant atual.
     */
    public static function login(string $email, string $password): bool
    {
        $userModel = new \App\Models\User();
        $user = self::isSharedHost()
            ? $userModel->findForSharedLogin($email)
            : $userModel->findByEmail($email);

        if ($user && self::verifyPassword($password, $user->password)) {
            if (!self::prepareTenantForUser($user)) {
                AuditLogger::log('login_tenant_unavailable', ['user_id' => $user->id ?? null]);
                return false;
            }
            self::loginAsUser($user);
            return true;
        }

        AuditLogger::log('login_failed', [
            'email' => $email,
            'tenant_id' => TenantContext::has() ? TenantContext::id() : null,
        ]);
        return false;
    }

    /**
     * Materializa a sessão de um usuário já autenticado.
     *
     * O model de usuário só retorna vínculo ativo com o tenant atual. A checagem
     * adicional abaixo impede a criação de sessão caso esse contrato seja violado.
     */
    public static function loginAsUser(object $user): void
    {
        $tenantId = TenantContext::id();
        if ((int) ($user->tenant_id ?? 0) !== $tenantId) {
            AuditLogger::log('login_tenant_mismatch', [
                'user_id' => $user->id ?? null,
                'tenant_id' => $tenantId,
            ]);
            throw new LogicException('O usuário não possui vínculo ativo com o tenant atual.');
        }

        self::regenerateSession();
        $_SESSION['user_id'] = (int) $user->id;
        $_SESSION['user_name'] = $user->name;
        $_SESSION['user_email'] = $user->email;
        $_SESSION['user_role'] = $user->tenant_role ?? $user->role ?? 'user';
        $_SESSION['active_tenant_id'] = $tenantId;
        $_SESSION['login_time'] = time();

        $userLocale = (string) ($user->locale ?? 'pt_BR');
        if (Lang::instance()->isSupported($userLocale)) {
            Lang::instance()->setLocale($userLocale);
        }

        AuditLogger::log('login_success', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $_SESSION['user_role'],
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Prepara o contexto de tenant do usuário no domínio compartilhado. O tenant
     * só é aceito quando o vínculo user_tenants está ativo no banco.
     */
    public static function prepareTenantForUser(object $user): bool
    {
        if (!self::isSharedHost()) {
            return true;
        }
        $tenantId = (int) ($user->tenant_id ?? 0);
        $userId = (int) ($user->id ?? 0);
        if ($tenantId <= 0 || $userId <= 0) {
            return false;
        }
        $tenant = (new \App\Models\Tenant())->findActiveForUser($tenantId, $userId);
        if (!$tenant) {
            return false;
        }
        TenantContext::set($tenant);
        $_SESSION['active_tenant_id'] = (int) $tenant->id;
        return true;
    }

    /**
     * Resolve o tenant padrão de um usuário para operações de recuperação de senha
     * no domínio compartilhado, sempre revalidando o vínculo ativo no banco.
     */
    public static function prepareTenantForUserId(int $userId): bool
    {
        if (!self::isSharedHost()) {
            return true;
        }
        $userModel = new \App\Models\User();
        $tenantId = $userModel->findDefaultTenantId($userId);
        if ($tenantId <= 0) {
            return false;
        }
        $tenant = (new \App\Models\Tenant())->findActiveForUser($tenantId, $userId);
        if (!$tenant) {
            return false;
        }
        TenantContext::set($tenant);
        $_SESSION['active_tenant_id'] = (int) $tenant->id;
        return true;
    }

    /**
     * Redirecionamento pós-login, sem confiar em parâmetros de URL externos.
     */
    public static function postLoginPath(): string
    {
        $controlTenantId = (int) ($_ENV['SAAS_CONTROL_TENANT_ID'] ?? 0);
        if (self::hasRole('saas_owner') && $controlTenantId > 0 && TenantContext::has()
            && TenantContext::id() === $controlTenantId) {
            return '/painel';
        }
        return '/dashboard';
    }

    /**
     * Identifica o host compartilhado estritamente por configuração de servidor.
     */
    public static function isSharedHost(): bool
    {
        $configured = strtolower(trim((string) ($_ENV['SAAS_SHARED_HOST'] ?? '')));
        $host = strtolower(trim((string) ($_SERVER['HTTP_HOST'] ?? '')));
        $host = preg_replace('/:\\d+$/', '', $host) ?? '';
        return $configured !== '' && hash_equals($configured, $host);
    }

    /**
     * Verifica se o usuário está autenticado.
     */
    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * Retorna os dados do usuário logado e o tenant da requisição atual.
     */
    public static function user(): ?object
    {
        if (!self::check()) {
            return null;
        }

        return (object) [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'] ?? null,
            'role' => $_SESSION['user_role'],
            'tenant_id' => TenantContext::id(),
        ];
    }

    /**
     * Finaliza a sessão do usuário e remove o contexto de tenant.
     */
    public static function logout(): void
    {
        AuditLogger::log('logout', [
            'tenant_id' => TenantContext::has() ? TenantContext::id() : null,
        ]);
        TenantContext::clear();
        session_unset();
        session_destroy();

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
    }

    /**
     * Regenera o ID da sessão para prevenir ataques de fixação.
     */
    private static function regenerateSession(): void
    {
        session_regenerate_id(true);
    }

    /**
     * Verifica se o usuário autenticado tem uma permissão no papel do tenant.
     */
    public static function can(string $permission): bool
    {
        if (!self::check()) {
            return false;
        }

        $role = $_SESSION['user_role'] ?? '';
        $provider = new Permission();
        $permissions = $provider->getPermissionsForRole($role);

        return in_array($permission, $permissions, true);
    }

    /**
     * Verifica se o usuário tem um papel específico no tenant atual.
     */
    public static function hasRole(string $role): bool
    {
        return ($_SESSION['user_role'] ?? '') === strtolower($role);
    }
}
