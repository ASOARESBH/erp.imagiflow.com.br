<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\TenantContext;
use App\Core\View;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SaasImpersonationService;
use RuntimeException;

class SaasImpersonacaoController extends Controller
{
    private SaasImpersonationService $service;

    public function __construct()
    {
        $this->service = new SaasImpersonationService();
    }

    /**
     * Entrada pública controlada por token de uso único no host do tenant alvo.
     */
    public function entrar(string $token): void
    {
        try {
            $returnToken = (string) ($_GET['return'] ?? '');
            if ($returnToken === '') {
                throw new RuntimeException('Token de retorno ausente.');
            }
            $this->service->enterTargetTenant($token, $returnToken);
            header('Location: /dashboard');
        } catch (\Throwable $exception) {
            http_response_code(403);
            echo '<h1>403 - Impersonação inválida</h1><p>O link expirou, já foi utilizado ou não corresponde a este tenant.</p>';
        }
        exit();
    }

    /**
     * Encerramento no tenant alvo: limpa apenas a sessão temporária e redireciona
     * ao control-plane, onde a sessão original permanece intacta por host.
     */
    public function sair(): void
    {
        $this->assertCsrf();
        $origin = $_SESSION['impersonation_origin'] ?? null;
        if (!is_array($origin) || empty($origin['return_token'])) {
            http_response_code(403);
            exit('403 - Nenhuma impersonação ativa.');
        }

        $controlTenant = (new Tenant())->findActiveById((int) ($_ENV['SAAS_CONTROL_TENANT_ID'] ?? 0));
        $returnToken = (string) $origin['return_token'];
        session_unset();
        session_destroy();

        if (!$controlTenant) {
            http_response_code(500);
            exit('Tenant de controle não configurado.');
        }
        header('Location: https://' . $controlTenant->domain . '/saas-admin/impersonacao/retornar/' . rawurlencode($returnToken));
        exit();
    }

    /**
     * Recebe o retorno no host de controle e recupera a sessão SaaS já preservada nesse domínio.
     */
    public function retornar(string $token): void
    {
        $pending = $_SESSION['saas_impersonation_pending'] ?? null;
        if (!is_array($pending) || empty($pending['return_token'])
            || !hash_equals((string) $pending['return_token'], $token)) {
            http_response_code(403);
            exit('403 - Retorno de impersonação inválido.');
        }

        $log = $this->service->finalizeReturn($token);
        if (!$log || (int) $log->id !== (int) $pending['log_id']) {
            http_response_code(403);
            exit('403 - Sessão de impersonação expirada ou já encerrada.');
        }

        unset($_SESSION['saas_impersonation_pending']);
        header('Location: /saas-admin/empresas?impersonation=ended');
        exit();
    }

    public function logs(): void
    {
        View::render('saas_admin/impersonacao/logs', [
            'title' => 'Auditoria de Impersonação',
            'logs' => $this->service->listRecentImpersonations(),
            '_layout' => 'erp',
        ]);
    }

    private function assertCsrf(): void
    {
        if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), (string) ($_POST['csrf_token'] ?? ''))) {
            http_response_code(419);
            exit('419 - Token CSRF inválido');
        }
    }
}
