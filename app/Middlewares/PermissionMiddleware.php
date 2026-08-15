<?php

namespace App\Middlewares;

use App\Core\Auth;
use App\Core\Middleware;
use App\Core\PlanGate;
use App\Core\Audit\AuditLogger;

class PermissionMiddleware extends Middleware
{
    protected string $permission;

    /** @var array<string, string> */
    private const PLAN_MODULE_BY_PERMISSION = [
        'view_clients' => 'clientes', 'create_clients' => 'clientes', 'edit_clients' => 'clientes', 'delete_clients' => 'clientes',
        'view_colaboradores' => 'colaboradores', 'create_colaboradores' => 'colaboradores',
        'view_crm' => 'crm', 'view_financeiro_pagar' => 'financeiro_pagar', 'view_financeiro_receber' => 'financeiro_receber',
        'view_fornecedores' => 'fornecedores', 'view_estoque' => 'estoque', 'view_marketing' => 'marketing',
        'view_rdv' => 'rdv', 'view_hub_ia' => 'hub_ia', 'view_manutencao' => 'manutencao',
    ];

    public function __construct(string $permission = '')
    {
        $this->permission = $permission;
    }

    public function handle(): void
    {
        $module = self::PLAN_MODULE_BY_PERMISSION[$this->permission] ?? null;
        if (!Auth::can($this->permission) || ($module !== null && !PlanGate::allows($module))) {
            AuditLogger::log('access_denied', ['permission' => $this->permission, 'module' => $module]);
            http_response_code(403);

            if ($this->isAjax()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'message' => 'Acesso negado: você não tem permissão para esta ação.',
                ]);
                exit();
            }

            echo "<h1>403 - Acesso Negado</h1>";
            echo "<p>Você não tem permissão para acessar esta área: <b>{$this->permission}</b></p>";
            echo "<a href='/dashboard'>Voltar ao Painel</a>";
            exit();
        }
    }

    private function isAjax(): bool
    {
        $xrw = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
        if ($xrw === 'xmlhttprequest') {
            return true;
        }
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (str_contains($accept, 'application/json') && !str_contains($accept, 'text/html')) {
            return true;
        }
        $ct = $_SERVER['CONTENT_TYPE'] ?? '';
        return str_contains($ct, 'application/json');
    }
}
