<?php

namespace App\Core;

use App\Models\PlanoModulo;

/**
 * Segunda camada de acesso: recursos liberados pelo plano do tenant.
 * RBAC continua independente e deve ser validado antes deste gate.
 */
final class PlanGate
{
    /** @var array<int, array<string, bool>> */
    private static array $cache = [];

    private function __construct()
    {
    }

    public static function allows(string $moduloSlug): bool
    {
        $tenant = TenantContext::tenant();
        $planoId = (int) ($tenant->plano_id ?? 0);

        // Compatibilidade: tenants históricos sem plano continuam operacionais
        // até receberem uma assinatura explicitamente pelo control-plane.
        if ($planoId <= 0) {
            return true;
        }

        if (!isset(self::$cache[$planoId])) {
            self::$cache[$planoId] = [];
            foreach ((new PlanoModulo())->activeSlugsForPlan($planoId) as $slug) {
                self::$cache[$planoId][$slug] = true;
            }
        }

        return isset(self::$cache[$planoId][strtolower(trim($moduloSlug))]);
    }

    public static function clear(): void
    {
        self::$cache = [];
    }
}
