<?php

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Catálogo global de planos SaaS.
 * Este model é acessado apenas pelo control-plane protegido por SaasAdminMiddleware.
 */
class Plano extends Model
{
    protected string $table = 'planos';

    public function listAll(): array
    {
        $stmt = $this->pdo->query(
            "SELECT p.*, COUNT(pm.id) AS total_modulos_ativos
             FROM {$this->table} p
             LEFT JOIN plano_modulos pm ON pm.plano_id = p.id AND pm.ativo = 1
             GROUP BY p.id
             ORDER BY p.ordem ASC, p.nome ASC"
        );

        return $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];
    }

    public function listActive(): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table}
             WHERE status = 'ativo'
             ORDER BY ordem ASC, nome ASC"
        );
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];
    }

    public function findById(int $id): object|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);

        return $stmt->fetch(PDO::FETCH_OBJ) ?: false;
    }

    public function findBySlug(string $slug): object|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE slug = :slug LIMIT 1");
        $stmt->execute([':slug' => strtolower(trim($slug))]);

        return $stmt->fetch(PDO::FETCH_OBJ) ?: false;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->table}
                (slug, nome, descricao, preco_mensal, limite_usuarios, ordem, status, created_at, updated_at)
             VALUES
                (:slug, :nome, :descricao, :preco_mensal, :limite_usuarios, :ordem, :status, NOW(), NOW())"
        );
        $stmt->execute([
            ':slug' => $data['slug'],
            ':nome' => $data['nome'],
            ':descricao' => $data['descricao'] ?: null,
            ':preco_mensal' => $data['preco_mensal'],
            ':limite_usuarios' => $data['limite_usuarios'],
            ':ordem' => $data['ordem'],
            ':status' => $data['status'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table}
             SET slug = :slug,
                 nome = :nome,
                 descricao = :descricao,
                 preco_mensal = :preco_mensal,
                 limite_usuarios = :limite_usuarios,
                 ordem = :ordem,
                 status = :status,
                 updated_at = NOW()
             WHERE id = :id"
        );

        return $stmt->execute([
            ':id' => $id,
            ':slug' => $data['slug'],
            ':nome' => $data['nome'],
            ':descricao' => $data['descricao'] ?: null,
            ':preco_mensal' => $data['preco_mensal'],
            ':limite_usuarios' => $data['limite_usuarios'],
            ':ordem' => $data['ordem'],
            ':status' => $data['status'],
        ]);
    }
}
