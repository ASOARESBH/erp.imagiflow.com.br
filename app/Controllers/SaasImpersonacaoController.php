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
     * Encerramento no mesmo host: recupera o tenant de controle e a sessão SaaS
     * original após validar o token de retorno de uso único.
     */
    public function sair(): void
    {
        $this->assertCsrf();
        $origin = $_SESSION['impersonation_origin'] ?? null;
        if (!is_array($origin)) {
            http_response_code(403);
            exit('403 - Nenhuma impersonação ativa.');
        }
        try {
            $this->service->exitToControlTenant($origin);
            header('Location: /painel/empresas?impersonation=ended');
        } catch (\Throwable $exception) {
            http_response_code(403);
            echo '403 - Não foi possível encerrar a impersonação com segurança.';
        }
        exit();
    }

    /**
     * Compatibilidade para links de retorno antigos: o fluxo atual encerra no
     * mesmo POST protegido por CSRF e não depende de outro host.
     */
    public function retornar(string $token): void
    {
        http_response_code(410);
        exit('410 - Fluxo de retorno substituído pelo encerramento no mesmo domínio.');
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
