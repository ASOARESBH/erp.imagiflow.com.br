<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class PlanoConta extends Model
{
    protected string $table = 'plano_contas';

    public function findById(int $id): object|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: false;
    }

    public function findByIdForTenant(int $id, int $tenantId): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table} WHERE id = :id AND tenant_id = :tenant_id LIMIT 1"
        );
        $stmt->execute([':id' => $id, ':tenant_id' => $tenantId]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: false;
    }

    /**
     * Compatibilidade temporária para módulos legados ainda centrados em
     * usuário. O CRUD principal deve usar findByTenantId().
     */
    public function findByUsuarioId(int $usuarioId, array $filtros = []): array
    {
        return $this->listByScope('usuario_id', $usuarioId, $filtros);
    }

    public function findByTenantId(int $tenantId, array $filtros = []): array
    {
        return $this->listByScope('tenant_id', $tenantId, $filtros);
    }

    public function listAtivasParaPai(int $usuarioId, ?int $excludeId = null): array
    {
        $sql = "SELECT id, codigo, nome, nivel FROM {$this->table} WHERE usuario_id = :usuario_id AND status = 'ativo'";
        $params = [':usuario_id' => $usuarioId];

        if ($excludeId !== null) {
            $sql .= ' AND id <> :exclude_id';
            $params[':exclude_id'] = $excludeId;
        }

        $sql .= ' ORDER BY codigo ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function listAtivasParaPaiByTenant(int $tenantId, ?int $excludeId = null): array
    {
        $sql = "SELECT id, codigo, nome, nivel FROM {$this->table} WHERE tenant_id = :tenant_id AND status = 'ativo'";
        $params = [':tenant_id' => $tenantId];

        if ($excludeId !== null) {
            $sql .= ' AND id <> :exclude_id';
            $params[':exclude_id'] = $excludeId;
        }

        $sql .= ' ORDER BY codigo ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function findByTenantAndCode(int $tenantId, string $codigo): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table} WHERE tenant_id = :tenant_id AND codigo = :codigo LIMIT 1"
        );
        $stmt->execute([':tenant_id' => $tenantId, ':codigo' => trim($codigo)]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: false;
    }

    public function findByTenantAndTemplateCode(int $tenantId, string $templateCode): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table}
             WHERE tenant_id = :tenant_id AND modelo_padrao_codigo = :modelo
             LIMIT 1"
        );
        $stmt->execute([':tenant_id' => $tenantId, ':modelo' => $templateCode]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: false;
    }

    public function create(array $data): string|false
    {
        return $this->createForTenant($data);
    }

    public function createForTenant(array $data): string|false
    {
        $tenantId = (int) ($data['tenant_id'] ?? 0);
        $usuarioId = (int) ($data['usuario_id'] ?? 0);
        if ($tenantId <= 0 || $usuarioId <= 0) {
            return false;
        }

        $contaPaiId = $this->resolveParentId($tenantId, $data['conta_pai_id'] ?? null);
        if ($contaPaiId === false) {
            return false;
        }
        $nivel = $this->resolveLevel($tenantId, $contaPaiId, $data['nivel'] ?? null);
        if ($nivel === false) {
            return false;
        }

        $sql = "INSERT INTO {$this->table}
                (tenant_id, usuario_id, codigo, nome, tipo, nivel, conta_pai_id, modelo_padrao_codigo, status)
                VALUES (:tenant_id, :usuario_id, :codigo, :nome, :tipo, :nivel, :conta_pai_id, :modelo_padrao_codigo, :status)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':tenant_id', $tenantId, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':codigo', trim((string) $data['codigo']));
        $stmt->bindValue(':nome', trim((string) $data['nome']));
        $stmt->bindValue(':tipo', (string) $data['tipo']);
        $stmt->bindValue(':nivel', $nivel, PDO::PARAM_INT);
        $stmt->bindValue(':conta_pai_id', $contaPaiId, $contaPaiId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':modelo_padrao_codigo', $data['modelo_padrao_codigo'] ?? null, ($data['modelo_padrao_codigo'] ?? null) === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':status', $data['status'] ?? 'ativo');

        return $stmt->execute() ? $this->pdo->lastInsertId() : false;
    }

    public function update(int $id, array $data): bool
    {
        $conta = $this->findById($id);
        if (!$conta) {
            return false;
        }
        return $this->updateForTenant($id, (int) $conta->tenant_id, $data);
    }

    public function updateForTenant(int $id, int $tenantId, array $data): bool
    {
        $conta = $this->findByIdForTenant($id, $tenantId);
        if (!$conta) {
            return false;
        }

        $parentRaw = array_key_exists('conta_pai_id', $data) ? $data['conta_pai_id'] : $conta->conta_pai_id;
        $contaPaiId = $this->resolveParentId($tenantId, $parentRaw, $id);
        if ($contaPaiId === false) {
            return false;
        }
        $nivel = $this->resolveLevel($tenantId, $contaPaiId, null);
        if ($nivel === false) {
            return false;
        }

        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table}
             SET codigo = :codigo, nome = :nome, tipo = :tipo,
                 nivel = :nivel, conta_pai_id = :conta_pai_id, status = :status
             WHERE id = :id AND tenant_id = :tenant_id"
        );

        return $stmt->execute([
            ':id' => $id,
            ':tenant_id' => $tenantId,
            ':codigo' => trim((string) $data['codigo']),
            ':nome' => trim((string) $data['nome']),
            ':tipo' => $data['tipo'],
            ':nivel' => $nivel,
            ':conta_pai_id' => $contaPaiId,
            ':status' => $data['status'] ?? 'ativo',
        ]);
    }

    public function delete(int $id): bool
    {
        $conta = $this->findById($id);
        if (!$conta) {
            return false;
        }
        return $this->deleteForTenant($id, (int) $conta->tenant_id);
    }

    public function deleteForTenant(int $id, int $tenantId): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table} SET status = 'inativo' WHERE id = :id AND tenant_id = :tenant_id"
        );
        return $stmt->execute([':id' => $id, ':tenant_id' => $tenantId]);
    }

    private function listByScope(string $column, int $scopeId, array $filtros): array
    {
        if (!in_array($column, ['usuario_id', 'tenant_id'], true)) {
            return [];
        }

        $where = ["{$column} = :scope_id"];
        $params = [':scope_id' => $scopeId];
        $status = $filtros['status'] ?? 'ativo';
        if ($status !== '') {
            $where[] = 'status = :status';
            $params[':status'] = $status;
        }
        $tipo = $filtros['tipo'] ?? '';
        if ($tipo !== '') {
            $where[] = 'tipo = :tipo';
            $params[':tipo'] = $tipo;
        }
        $query = trim((string) ($filtros['pesquisa'] ?? ''));
        if ($query !== '') {
            $where[] = '(codigo LIKE :q1 OR nome LIKE :q2)';
            $params[':q1'] = '%' . $query . '%';
            $params[':q2'] = '%' . $query . '%';
        }

        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $where) . ' ORDER BY codigo ASC'
        );
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    private function resolveParentId(int $tenantId, mixed $parentId, ?int $excludeId = null): int|false|null
    {
        if ($parentId === '' || $parentId === null || (int) $parentId <= 0) {
            return null;
        }
        $parentId = (int) $parentId;
        if ($excludeId !== null && $parentId === $excludeId) {
            return false;
        }
        $parent = $this->findByIdForTenant($parentId, $tenantId);
        return $parent ? $parentId : false;
    }

    private function resolveLevel(int $tenantId, ?int $parentId, mixed $fallback): int|false
    {
        if ($parentId === null) {
            return is_numeric($fallback) && (int) $fallback > 0 ? (int) $fallback : 1;
        }
        $parent = $this->findByIdForTenant($parentId, $tenantId);
        return $parent ? ((int) $parent->nivel + 1) : false;
    }
}
