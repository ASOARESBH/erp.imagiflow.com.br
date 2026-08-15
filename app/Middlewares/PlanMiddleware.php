<?php

namespace App\Middlewares;

use App\Core\Audit\AuditLogger;
use App\Core\Middleware;
use App\Core\PlanGate;

class PlanMiddleware extends Middleware
{
    protected string $moduleSlug;

    public function __construct(string $moduleSlug = '')
    {
        $this->moduleSlug = strtolower(trim($moduleSlug));
    }

    public function handle(): void
    {
        if ($this->moduleSlug !== '' && !PlanGate::allows($this->moduleSlug)) {
            AuditLogger::log('plan_module_access_denied', [
                'module' => $this->moduleSlug,
            ]);

            http_response_code(403);
            if ($this->isAjax()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'message' => 'Este recurso não está disponível no plano contratado.',
                    'module' => $this->moduleSlug,
                ]);
                exit();
            }

            echo '<h1>403 - Recurso indisponível</h1>';
            echo '<p>Este recurso não está disponível no plano contratado.</p>';
            echo '<a href="/dashboard">Voltar ao Painel</a>';
            exit();
        }
    }

    private function isAjax(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
            || str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json');
    }
}
