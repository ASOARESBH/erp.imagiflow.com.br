<?php

namespace App\Middlewares;

use App\Core\Auth;
use App\Core\Audit\AuditLogger;
use App\Core\Middleware;
use App\Core\TenantContext;

/**
 * Garante que as rotas /saas/* só existam no tenant de controle e para saas_owner.
 */
class SaasAdminMiddleware extends Middleware
{
    public function handle(): void
    {
        if (isset($_SESSION['impersonation_origin'])) {
            AuditLogger::log('saas_admin_impersonation_blocked', [
                'log_id' => $_SESSION['impersonation_origin']['log_id'] ?? null,
            ]);
            http_response_code(403);
            echo '<h1>403 - Impersonação ativa</h1><p>Encerre a impersonação antes de retornar ao Painel SaaS.</p>';
            exit();
        }

        $controlTenantId = Auth::controlTenantId();
        $currentTenantId = TenantContext::id();

        if ($controlTenantId <= 0 || $currentTenantId !== $controlTenantId) {
            AuditLogger::log('saas_admin_tenant_denied', [
                'tenant_id' => $currentTenantId,
                'configured_control_tenant_id' => $controlTenantId,
            ]);
            http_response_code(404);
            echo '404 - Página não encontrada';
            exit();
        }

        if (!Auth::check() || !Auth::hasRole('saas_owner') || !Auth::can('access_saas_admin')) {
            AuditLogger::log('saas_admin_role_denied', [
                'tenant_id' => $currentTenantId,
                'user_id' => $_SESSION['user_id'] ?? null,
                'role' => $_SESSION['user_role'] ?? null,
            ]);
            http_response_code(403);
            echo '<h1>403 - Acesso Negado</h1><p>Esta área é exclusiva da administração SaaS.</p>';
            exit();
        }
    }
}
