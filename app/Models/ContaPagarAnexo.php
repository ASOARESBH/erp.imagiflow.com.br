<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class ContaPagarAnexo extends Model
{
    protected string $table = 'contas_pagar_anexos';

    public function findById(int $id): object|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function findByIdForTenant(int $id, int $tenantId): object|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = :id AND tenant_id = :tenant_id");
        $stmt->execute([':id' => $id, ':tenant_id' => $tenantId]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /** @return object[] */
    public function findByContaId(int $contaPagarId, int $tenantId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table}
             WHERE conta_pagar_id = :conta_pagar_id AND tenant_id = :tenant_id
             ORDER BY id DESC"
        );
        $stmt->execute([':conta_pagar_id' => $contaPagarId, ':tenant_id' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function create(array $data): string|false
    {
        $sql = "INSERT INTO {$this->table}
                (tenant_id, usuario_id, conta_pagar_id, file_path, original_name, mime_type, file_size)
                VALUES (:tenant_id, :usuario_id, :conta_pagar_id, :file_path, :original_name, :mime_type, :file_size)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':tenant_id', (int) ($data['tenant_id'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', (int) ($data['usuario_id'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':conta_pagar_id', (int) ($data['conta_pagar_id'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':file_path', (string) ($data['file_path'] ?? ''));
        $stmt->bindValue(':original_name', (string) ($data['original_name'] ?? 'anexo'));
        $stmt->bindValue(':mime_type', $data['mime_type'] ?? null);
        $stmt->bindValue(':file_size', $data['file_size'] ?? null);
        return $stmt->execute() ? $this->pdo->lastInsertId() : false;
    }

    public function deleteForTenant(int $id, int $tenantId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = :id AND tenant_id = :tenant_id");
        return $stmt->execute([':id' => $id, ':tenant_id' => $tenantId]);
    }
}
