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
            // No domínio compartilhado, o tenant de uma sessão autenticada é
            // recuperado exclusivamente a partir de um vínculo ativo no banco.
            // Hosts de tenant dedicados continuam sendo resolvidos pelo host.
            $sessionUserId = (int) ($_SESSION['user_id'] ?? $_SESSION['2fa_pending_user_id'] ?? 0);
            $sessionTenantId = (int) ($_SESSION['active_tenant_id'] ?? $_SESSION['2fa_pending_tenant_id'] ?? 0);
            if ($this->isSharedHost($host) && $sessionUserId > 0 && $sessionTenantId > 0) {
                $tenant = $tenantModel->findActiveForUser($sessionTenantId, $sessionUserId);
            } else {
                $tenant = $tenantModel->findActiveByHost($host);
            }
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

    private function isSharedHost(string $host): bool
    {
        $configured = strtolower(trim((string) ($_ENV['SAAS_SHARED_HOST'] ?? '')));
        if ($configured === '') {
            return false;
        }

        return hash_equals($configured, $host);
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
