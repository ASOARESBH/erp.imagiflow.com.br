<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Logger;
use App\Core\Middleware;

/**
 * Protege requisições que alteram estado e pertencem a sessões autenticadas.
 *
 * Os webhooks e APIs com autenticação própria não usam este middleware: a
 * proteção é aplicada somente aos grupos de rotas internas e do portal.
 */
class CsrfMiddleware extends Middleware
{
    public function handle(): void
    {
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            return;
        }

        $sessionToken = (string) ($_SESSION['csrf_token'] ?? '');
        $submittedToken = $this->submittedToken();

        if ($sessionToken !== '' && $submittedToken !== '' && hash_equals($sessionToken, $submittedToken)) {
            return;
        }

        $this->logRejectedRequest();
        $this->rejectRequest();
    }

    private function submittedToken(): string
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_SERVER['HTTP_X_CSRF_TOKEN_VALUE'] ?? '';
        if (is_string($token) && $token !== '') {
            return $token;
        }

        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
        if (strpos($contentType, 'application/json') === false) {
            return '';
        }

        $payload = json_decode((string) file_get_contents('php://input'), true);
        return is_array($payload) && isset($payload['csrf_token']) && is_string($payload['csrf_token'])
            ? $payload['csrf_token']
            : '';
    }

    private function logRejectedRequest(): void
    {
        try {
            (new Logger())->warning('Requisição POST bloqueada por CSRF.', [
                'uri' => (string) ($_SERVER['REQUEST_URI'] ?? ''),
                'method' => (string) ($_SERVER['REQUEST_METHOD'] ?? ''),
                'user_id' => (int) ($_SESSION['user_id'] ?? 0),
                'ip' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
            ]);
        } catch (\Throwable $exception) {
            error_log('Falha ao registrar bloqueio CSRF: ' . $exception->getMessage());
        }
    }

    private function rejectRequest(): void
    {
        http_response_code(419);

        if ($this->expectsJson()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Sessão expirada ou solicitação inválida. Atualize a página e tente novamente.',
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }

        $redirect = $this->safeReferer();
        if ($redirect !== null) {
            $separator = strpos($redirect, '?') === false ? '?' : '&';
            header('Location: ' . $redirect . $separator . 'error=csrf');
            exit();
        }

        header('Content-Type: text/html; charset=utf-8');
        echo 'Sessão expirada ou solicitação inválida. Atualize a página e tente novamente.';
        exit();
    }

    private function expectsJson(): bool
    {
        $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');

        return strpos($accept, 'application/json') !== false
            || strpos($contentType, 'application/json') !== false
            || strpos($uri, '/api/') === 0;
    }

    private function safeReferer(): ?string
    {
        $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        if ($referer === '') {
            return null;
        }

        $parts = parse_url($referer);
        $refererHost = strtolower((string) ($parts['host'] ?? ''));
        $requestHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        if ($refererHost === '' || $requestHost === '' || !hash_equals($requestHost, $refererHost)) {
            return null;
        }

        $path = (string) ($parts['path'] ?? '/');
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';
        return $path . $query;
    }
}
