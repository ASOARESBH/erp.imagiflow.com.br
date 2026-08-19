<?php

namespace App\Middlewares;

use App\Core\Auth;
use App\Core\Logger;
use App\Core\Middleware;
use App\Core\TenantContext;
use App\Models\ApiToken;

class ApiTokenAuthMiddleware extends Middleware
{
    public function handle(): void
    {
        $plainToken = $this->bearerToken();
        if ($plainToken === null) {
            $this->deny(401, 'Token de acesso ausente ou inválido.');
        }

        $token = (new ApiToken())->findActiveByPlainToken($plainToken);
        if (!$token) {
            $this->deny(401, 'Sua sessão móvel expirou ou foi revogada.');
        }

        if (!TenantContext::has() || (int) $token->tenant_id !== TenantContext::id()) {
            (new Logger())->warning('Tentativa de uso de token móvel em tenant divergente', [
                'token_id' => $token->id ?? null,
                'token_tenant_id' => $token->tenant_id ?? null,
                'request_tenant_id' => TenantContext::has() ? TenantContext::id() : null,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
            $this->deny(403, 'O token não pertence ao ambiente informado.');
        }

        try {
            Auth::loginFromApiToken($token);
            (new ApiToken())->touch((int) $token->id);
        } catch (\Throwable $exception) {
            (new Logger())->error('Falha ao materializar autenticação por token móvel', [
                'token_id' => $token->id ?? null,
                'error' => $exception->getMessage(),
            ]);
            $this->deny(401, 'Não foi possível validar sua sessão móvel.');
        }
    }

    private function bearerToken(): ?string
    {
        $header = trim((string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
        if ($header === '' && function_exists('getallheaders')) {
            $headers = getallheaders();
            $header = trim((string) ($headers['Authorization'] ?? $headers['authorization'] ?? ''));
        }

        if (!preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return null;
        }

        $token = trim($matches[1]);
        return $token !== '' ? $token : null;
    }

    private function deny(int $status, string $message): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => $message,
            'errors' => ['authorization' => [$message]],
        ]);
        exit();
    }
}
