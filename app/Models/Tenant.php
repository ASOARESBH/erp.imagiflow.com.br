<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class Tenant extends Model
{
    protected string $table = 'tenants';

    public function findActiveByHost(string $host): object|false
    {
        $host = strtolower(trim($host));
        if ($host === '') {
            return false;
        }

        $stmt = $this->pdo->prepare(
            "SELECT *
             FROM {$this->table}
             WHERE status = 'active'
               AND (LOWER(domain) = :host OR LOWER(subdomain) = :host)
             LIMIT 1"
        );
        $stmt->execute([':host' => $host]);

        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function findActiveBySlug(string $slug): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table} WHERE slug = :slug AND status = 'active' LIMIT 1"
        );
        $stmt->execute([':slug' => strtolower(trim($slug))]);

        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function findActiveById(int $id): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table} WHERE id = :id AND status = 'active' LIMIT 1"
        );
        $stmt->execute([':id' => $id]);

        return $stmt->fetch(PDO::FETCH_OBJ);
    }
}
