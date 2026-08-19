<?php

namespace App\Models;

use App\Core\Logger;
use App\Core\Model;
use App\Core\TenantContext;
use PDO;

class ColaboradorLocalizacao extends Model
{
    private const CONTEXTOS = [
        'cliente_create',
        'cliente_update',
        'crm_interacao',
        'rdv_visita',
        'check_in_manual',
    ];

    public function create(array $data): int|false
    {
        $contexto = (string) ($data['contexto'] ?? '');
        if (!in_array($contexto, self::CONTEXTOS, true)) {
            return false;
        }

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO colaboradores_localizacoes
                    (tenant_id, user_id, colaborador_id, latitude, longitude, accuracy_meters,
                     contexto, referencia_tabela, referencia_id, captured_at)
                 VALUES
                    (:tenant_id, :user_id, :colaborador_id, :latitude, :longitude, :accuracy_meters,
                     :contexto, :referencia_tabela, :referencia_id, :captured_at)'
            );
            $stmt->execute([
                ':tenant_id' => TenantContext::id(),
                ':user_id' => (int) $data['user_id'],
                ':colaborador_id' => !empty($data['colaborador_id']) ? (int) $data['colaborador_id'] : null,
                ':latitude' => $data['latitude'],
                ':longitude' => $data['longitude'],
                ':accuracy_meters' => $data['accuracy_meters'] ?? null,
                ':contexto' => $contexto,
                ':referencia_tabela' => $data['referencia_tabela'] ?? null,
                ':referencia_id' => !empty($data['referencia_id']) ? (int) $data['referencia_id'] : null,
                ':captured_at' => $data['captured_at'] ?? date('Y-m-d H:i:s'),
            ]);
            return (int) $this->pdo->lastInsertId();
        } catch (\Throwable $exception) {
            (new Logger())->error('Falha ao salvar localização pontual', [
                'tenant_id' => TenantContext::has() ? TenantContext::id() : null,
                'user_id' => $data['user_id'] ?? null,
                'contexto' => $contexto,
                'error' => $exception->getMessage(),
            ]);
            return false;
        }
    }

    /** Retorna a localização mais recente de cada colaborador no intervalo solicitado. */
    public function latestByTeam(?int $userId = null, int $days = 1): array
    {
        $days = max(1, min($days, 365));
        $where = 'l.tenant_id = :tenant_id AND l.captured_at >= DATE_SUB(NOW(), INTERVAL ' . $days . ' DAY)';
        $params = [':tenant_id' => TenantContext::id()];

        if ($userId !== null && $userId > 0) {
            $where .= ' AND l.user_id = :user_id';
            $params[':user_id'] = $userId;
        }

        $sql = 'SELECT l.*, u.name AS user_name, u.email AS user_email
                FROM colaboradores_localizacoes l
                INNER JOIN (
                    SELECT user_id, MAX(id) AS latest_id
                    FROM colaboradores_localizacoes
                    WHERE tenant_id = :sub_tenant_id
                      AND captured_at >= DATE_SUB(NOW(), INTERVAL ' . $days . ' DAY)'
                    . ($userId !== null && $userId > 0 ? ' AND user_id = :sub_user_id' : '') .
                    ' GROUP BY user_id
                ) latest ON latest.latest_id = l.id
                INNER JOIN users u ON u.id = l.user_id
                WHERE ' . $where . '
                ORDER BY l.captured_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $params[':sub_tenant_id'] = TenantContext::id();
        if ($userId !== null && $userId > 0) {
            $params[':sub_user_id'] = $userId;
        }
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function trailForUser(int $userId, int $days = 1): array
    {
        $days = max(1, min($days, 365));
        $stmt = $this->pdo->prepare(
            'SELECT l.*, u.name AS user_name
             FROM colaboradores_localizacoes l
             INNER JOIN users u ON u.id = l.user_id
             WHERE l.tenant_id = :tenant_id
               AND l.user_id = :user_id
               AND l.captured_at >= DATE_SUB(NOW(), INTERVAL ' . $days . ' DAY)
             ORDER BY l.captured_at ASC'
        );
        $stmt->execute([
            ':tenant_id' => TenantContext::id(),
            ':user_id' => $userId,
        ]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /** Exclusão por retenção somente para execução controlada por cron. */
    public function purgeOlderThanMonths(int $months = 12): int
    {
        $months = max(1, min($months, 60));
        try {
            $stmt = $this->pdo->prepare(
                'DELETE FROM colaboradores_localizacoes
                 WHERE captured_at < DATE_SUB(NOW(), INTERVAL ' . $months . ' MONTH)'
            );
            $stmt->execute();
            return $stmt->rowCount();
        } catch (\Throwable $exception) {
            (new Logger())->error('Falha ao executar retenção de localizações', [
                'months' => $months,
                'error' => $exception->getMessage(),
            ]);
            return 0;
        }
    }
}
