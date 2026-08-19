<?php

namespace App\Controllers\Api\Mobile\V1;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Logger;
use App\Core\TenantContext;
use App\Core\Audit\AuditLogger;

abstract class MobileController extends Controller
{
    protected function input(): array
    {
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
        if (str_contains($contentType, 'application/json')) {
            $payload = json_decode((string) file_get_contents('php://input'), true);
            return is_array($payload) ? $payload : [];
        }

        return $_POST;
    }

    protected function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    protected function success(mixed $data = null, ?string $message = null, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        $response = ['success' => true, 'data' => $this->normalize($data)];
        if ($message !== null) {
            $response['message'] = $message;
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }

    protected function error(string $message, array $errors = [], int $status = 400): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }

    protected function requirePermission(string $permission): void
    {
        if (!Auth::can($permission)) {
            $this->error('Você não possui permissão para realizar esta ação.', [
                'permission' => [$permission],
            ], 403);
        }
    }

    protected function currentUserId(): int
    {
        return (int) (Auth::user()?->id ?? 0);
    }

    protected function currentTenantId(): int
    {
        return TenantContext::id();
    }

    /** @return array{page:int,per_page:int,offset:int} */
    protected function pagination(): array
    {
        $page = max(1, (int) $this->query('page', 1));
        $perPage = min(100, max(1, (int) $this->query('per_page', 20)));
        return [
            'page' => $page,
            'per_page' => $perPage,
            'offset' => ($page - 1) * $perPage,
        ];
    }

    protected function paginated(array $items, int $total, array $pagination): array
    {
        return [
            'items' => $this->normalize($items),
            'page' => $pagination['page'],
            'per_page' => $pagination['per_page'],
            'total' => $total,
        ];
    }

    protected function audit(string $action, array $context = []): void
    {
        try {
            AuditLogger::log($action, array_merge($context, [
                'origem' => 'app_mobile',
                'tenant_id' => TenantContext::has() ? TenantContext::id() : null,
            ]));
        } catch (\Throwable $exception) {
            (new Logger())->warning('Falha não bloqueante na auditoria da API mobile', [
                'action' => $action,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    protected function normalize(mixed $value): mixed
    {
        if (is_object($value)) {
            return $this->normalize((array) $value);
        }
        if (is_array($value)) {
            $normalized = [];
            foreach ($value as $key => $item) {
                $normalized[$key] = $this->normalize($item);
            }
            return $normalized;
        }
        return $value;
    }

    protected function cleanString(mixed $value, int $maxLength = 255): string
    {
        return mb_substr(trim(strip_tags((string) $value)), 0, $maxLength);
    }
}
