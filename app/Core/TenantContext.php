<?php

namespace App\Core;

use LogicException;

/**
 * Contexto imutável do tenant durante uma requisição HTTP.
 *
 * O tenant é definido exclusivamente pelo middleware a partir do host da
 * requisição. Nenhum controller, rota ou parâmetro do cliente pode defini-lo.
 */
final class TenantContext
{
    private static ?object $tenant = null;

    private function __construct()
    {
    }

    public static function set(object $tenant): void
    {
        $tenantId = (int) ($tenant->id ?? 0);
        if ($tenantId <= 0) {
            throw new LogicException('Tenant inválido para o contexto da requisição.');
        }

        self::$tenant = $tenant;
        $_SESSION['tenant_id'] = $tenantId;
        $_SESSION['tenant_slug'] = (string) ($tenant->slug ?? '');
    }

    public static function has(): bool
    {
        return self::$tenant !== null;
    }

    public static function id(): int
    {
        if (self::$tenant === null) {
            throw new LogicException('Nenhum tenant foi definido para esta requisição.');
        }

        return (int) self::$tenant->id;
    }

    public static function tenant(): object
    {
        if (self::$tenant === null) {
            throw new LogicException('Nenhum tenant foi definido para esta requisição.');
        }

        return self::$tenant;
    }

    public static function matches(int $tenantId): bool
    {
        return self::has() && self::id() === $tenantId;
    }

    public static function clear(): void
    {
        self::$tenant = null;
        unset($_SESSION['tenant_id'], $_SESSION['tenant_slug']);
    }
}
