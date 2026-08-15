<?php

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Matriz global de módulos habilitados por plano SaaS.
 */
class PlanoModulo extends Model
{
    protected string $table = 'plano_modulos';

    public function activeSlugsForPlan(int $planoId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT modulo_slug
             FROM {$this->table}
             WHERE plano_id = :plano_id AND ativo = 1"
        );
        $stmt->execute([':plano_id' => $planoId]);

        return array_map(
            static fn (object $row): string => (string) $row->modulo_slug,
            $stmt->fetchAll(PDO::FETCH_OBJ) ?: []
        );
    }

    public function replaceForPlan(int $planoId, array $slugs): void
    {
        $this->pdo->prepare("DELETE FROM {$this->table} WHERE plano_id = :plano_id")
            ->execute([':plano_id' => $planoId]);

        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->table} (plano_id, modulo_slug, ativo)
             VALUES (:plano_id, :modulo_slug, 1)"
        );

        foreach (array_unique($slugs) as $slug) {
            $slug = strtolower(trim((string) $slug));
            if ($slug === '') {
                continue;
            }
            $stmt->execute([
                ':plano_id' => $planoId,
                ':modulo_slug' => $slug,
            ]);
        }
    }

    public function isEnabled(int $planoId, string $moduloSlug): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT 1 FROM {$this->table}
             WHERE plano_id = :plano_id
               AND modulo_slug = :modulo_slug
               AND ativo = 1
             LIMIT 1"
        );
        $stmt->execute([
            ':plano_id' => $planoId,
            ':modulo_slug' => strtolower(trim($moduloSlug)),
        ]);

        return (bool) $stmt->fetchColumn();
    }
}
