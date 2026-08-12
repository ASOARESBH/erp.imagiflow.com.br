<?php

namespace App\Middlewares;

use App\Core\Logger;
use App\Core\Middleware;
use App\Core\TenantContext;
use App\Models\Tenant;

class IdentifyTenantMiddleware extends Middleware
{
    public function handle(): void
    {
        $tenantModel = new Tenant();
        $host = $this->requestHost();
        $tenant = false;

        // Tarefas agendadas não possuem HTTP_HOST. Para elas, o tenant padrão
        // deve ser definido exclusivamente no ambiente do servidor, nunca em
        // argumentos de linha de comando ou parâmetros enviados por usuários.
        if (PHP_SAPI === 'cli') {
            $defaultSlug = trim((string) ($_ENV['TENANT_DEFAULT_SLUG'] ?? ''));
            if ($defaultSlug !== '') {
                $tenant = $tenantModel->findActiveBySlug($defaultSlug);
            }
        } else {
            $tenant = $tenantModel->findActiveByHost($host);
        }

        if (!$tenant) {
            (new Logger())->warning('Tenant não encontrado ou inativo para a requisição', [
                'host' => $host,
                'cli' => PHP_SAPI === 'cli',
            ]);
            $this->deny();
        }

        TenantContext::set($tenant);
    }

    private function requestHost(): string
    {
        $host = strtolower(trim((string) ($_SERVER['HTTP_HOST'] ?? '')));
        if ($host === '') {
            return '';
        }

        // Remove porta de desenvolvimento sem aceitar o host em parâmetros do cliente.
        return preg_replace('/:\\d+$/', '', $host) ?? '';
    }

    private function deny(): void
    {
        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
        $isJson = str_contains($accept, 'application/json') && !str_contains($accept, 'text/html');

        http_response_code(404);
        if ($isJson) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Ambiente não encontrado ou indisponível.',
            ]);
        } else {
            echo 'Ambiente não encontrado ou indisponível.';
        }

        exit();
    }
}
