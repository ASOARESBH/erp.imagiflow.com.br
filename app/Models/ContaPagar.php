<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class ContaPagar extends Model
{
    protected string $table = 'contas_pagar';

    public function findById(int $id): object|false
    {
        $sql = "SELECT cp.*, f.nome AS fornecedor_nome, pc.codigo AS plano_codigo, pc.nome AS plano_nome
                FROM {$this->table} cp
                LEFT JOIN fornecedores f ON f.id = cp.fornecedor_id
                LEFT JOIN plano_contas pc ON pc.id = cp.plano_conta_id
                WHERE cp.id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function findByIdForTenant(int $id, int $tenantId): object|false
    {
        $sql = "SELECT cp.*, f.nome AS fornecedor_nome, pc.codigo AS plano_codigo, pc.nome AS plano_nome
                FROM {$this->table} cp
                LEFT JOIN fornecedores f ON f.id = cp.fornecedor_id AND f.tenant_id = cp.tenant_id
                LEFT JOIN plano_contas pc ON pc.id = cp.plano_conta_id AND pc.tenant_id = cp.tenant_id
                WHERE cp.id = :id AND cp.tenant_id = :tenant_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id, ':tenant_id' => $tenantId]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Resume as contas a pagar em aberto do tenant para o painel financeiro.
     * Contas pagas e canceladas não integram os valores pendentes.
     *
     * @return array{em_aberto:float,quantidade_em_aberto:int,previsto_mes:float,quantidade_previsto_mes:int,em_atraso:float,quantidade_em_atraso:int}
     */
    public function resumoFinanceiroPorTenant(int $tenantId): array
    {
        $sql = "SELECT
                    COALESCE(SUM(CASE WHEN status = 'aberta' THEN valor ELSE 0 END), 0) AS em_aberto,
                    COALESCE(SUM(CASE WHEN status = 'aberta' THEN 1 ELSE 0 END), 0) AS quantidade_em_aberto,
                    COALESCE(SUM(CASE
                        WHEN status = 'aberta'
                         AND data_vencimento >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                         AND data_vencimento <= LAST_DAY(CURDATE())
                        THEN valor ELSE 0 END), 0) AS previsto_mes,
                    COALESCE(SUM(CASE
                        WHEN status = 'aberta'
                         AND data_vencimento >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                         AND data_vencimento <= LAST_DAY(CURDATE())
                        THEN 1 ELSE 0 END), 0) AS quantidade_previsto_mes,
                    COALESCE(SUM(CASE
                        WHEN status = 'aberta' AND data_vencimento < CURDATE()
                        THEN valor ELSE 0 END), 0) AS em_atraso,
                    COALESCE(SUM(CASE
                        WHEN status = 'aberta' AND data_vencimento < CURDATE()
                        THEN 1 ELSE 0 END), 0) AS quantidade_em_atraso
                FROM {$this->table}
                WHERE tenant_id = :tenant_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':tenant_id' => $tenantId]);
        $resumo = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'em_aberto' => (float) ($resumo['em_aberto'] ?? 0),
            'quantidade_em_aberto' => (int) ($resumo['quantidade_em_aberto'] ?? 0),
            'previsto_mes' => (float) ($resumo['previsto_mes'] ?? 0),
            'quantidade_previsto_mes' => (int) ($resumo['quantidade_previsto_mes'] ?? 0),
            'em_atraso' => (float) ($resumo['em_atraso'] ?? 0),
            'quantidade_em_atraso' => (int) ($resumo['quantidade_em_atraso'] ?? 0),
        ];
    }

    /** @return object[] */
    public function findByUsuarioId(int $usuarioId, array $filtros = []): array
    {
        $filtros['usuario_id'] = $usuarioId;
        return $this->findByFilters($filtros);
    }

    /** @return object[] */
    public function findByTenantId(int $tenantId, array $filtros = []): array
    {
        $filtros['tenant_id'] = $tenantId;
        return $this->findByFilters($filtros);
    }

    /** @return object[] */
    private function findByFilters(array $filtros): array
    {
        $where = [];
        $params = [];

        if (!empty($filtros['tenant_id'])) {
            $where[] = 'cp.tenant_id = :tenant_id';
            $params[':tenant_id'] = (int) $filtros['tenant_id'];
        }
        if (!empty($filtros['usuario_id'])) {
            $where[] = 'cp.usuario_id = :usuario_id';
            $params[':usuario_id'] = (int) $filtros['usuario_id'];
        }

        $status = (string) ($filtros['status'] ?? 'aberta');
        if ($status !== '' && $status !== 'todos') {
            $where[] = 'cp.status = :status';
            $params[':status'] = $status;
        }

        $q = trim((string) ($filtros['pesquisa'] ?? ''));
        if ($q !== '') {
            $where[] = '(cp.descricao LIKE :q1 OR f.nome LIKE :q2)';
            $like = '%' . $q . '%';
            $params[':q1'] = $like;
            $params[':q2'] = $like;
        }

        if ($where === []) {
            return [];
        }

        $sql = "SELECT cp.*, f.nome AS fornecedor_nome, pc.codigo AS plano_codigo
                FROM {$this->table} cp
                LEFT JOIN fornecedores f ON f.id = cp.fornecedor_id AND f.tenant_id = cp.tenant_id
                LEFT JOIN plano_contas pc ON pc.id = cp.plano_conta_id AND pc.tenant_id = cp.tenant_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY cp.data_vencimento ASC, cp.id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function create(array $data): string|false
    {
        $sql = "INSERT INTO {$this->table}
                (tenant_id, usuario_id, plano_conta_id, fornecedor_id, descricao, valor, data_vencimento, data_pagamento, codigo_barras,
                 recorrente, recorrencia_tipo, recorrencia_intervalo, recorrencia_modo, numero_parcela, total_parcelas, grupo_parcelas, status, observacoes)
                VALUES
                (:tenant_id, :usuario_id, :plano_conta_id, :fornecedor_id, :descricao, :valor, :data_vencimento, :data_pagamento, :codigo_barras,
                 :recorrente, :recorrencia_tipo, :recorrencia_intervalo, :recorrencia_modo, :numero_parcela, :total_parcelas, :grupo_parcelas, :status, :observacoes)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':tenant_id', (int) ($data['tenant_id'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', (int) ($data['usuario_id'] ?? 0), PDO::PARAM_INT);
        $this->bindNullableInt($stmt, ':plano_conta_id', $data['plano_conta_id'] ?? null);
        $this->bindNullableInt($stmt, ':fornecedor_id', $data['fornecedor_id'] ?? null);
        $stmt->bindValue(':descricao', trim((string) ($data['descricao'] ?? '')));
        $stmt->bindValue(':valor', $data['valor'] ?? '0.00');
        $stmt->bindValue(':data_vencimento', $data['data_vencimento'] ?? '');
        $this->bindNullableString($stmt, ':data_pagamento', $data['data_pagamento'] ?? null);
        $this->bindNullableString($stmt, ':codigo_barras', $data['codigo_barras'] ?? null);
        $stmt->bindValue(':recorrente', (int) ($data['recorrente'] ?? 0), PDO::PARAM_INT);
        $this->bindNullableString($stmt, ':recorrencia_tipo', $data['recorrencia_tipo'] ?? null);
        $this->bindNullableInt($stmt, ':recorrencia_intervalo', $data['recorrencia_intervalo'] ?? null);
        $this->bindNullableString($stmt, ':recorrencia_modo', $data['recorrencia_modo'] ?? null);
        $this->bindNullableInt($stmt, ':numero_parcela', $data['numero_parcela'] ?? null);
        $this->bindNullableInt($stmt, ':total_parcelas', $data['total_parcelas'] ?? null);
        $this->bindNullableString($stmt, ':grupo_parcelas', $data['grupo_parcelas'] ?? null);
        $stmt->bindValue(':status', $data['status'] ?? 'aberta');
        $this->bindNullableString($stmt, ':observacoes', $data['observacoes'] ?? null);

        return $stmt->execute() ? $this->pdo->lastInsertId() : false;
    }

    public function update(int $id, array $data): bool
    {
        return $this->updateWhere($id, null, $data);
    }

    public function updateForTenant(int $id, int $tenantId, array $data): bool
    {
        return $this->updateWhere($id, $tenantId, $data);
    }

    private function updateWhere(int $id, ?int $tenantId, array $data): bool
    {
        $allowedFields = [
            'plano_conta_id', 'fornecedor_id', 'descricao', 'valor', 'data_vencimento', 'data_pagamento',
            'codigo_barras', 'recorrente', 'recorrencia_tipo', 'recorrencia_intervalo', 'recorrencia_modo',
            'numero_parcela', 'total_parcelas', 'grupo_parcelas', 'status', 'observacoes',
        ];
        $updateFields = [];
        $params = [':id' => $id];

        foreach ($allowedFields as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }
            $updateFields[] = "{$field} = :{$field}";
            $value = $data[$field];
            if (in_array($field, ['plano_conta_id', 'fornecedor_id', 'recorrente', 'recorrencia_intervalo', 'numero_parcela', 'total_parcelas'], true)) {
                $params[":{$field}"] = ($value === '' || $value === null) ? null : (int) $value;
            } else {
                $params[":{$field}"] = ($value === '' || $value === null) ? null : $value;
            }
        }

        if ($updateFields === []) {
            return false;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $updateFields) . ' WHERE id = :id';
        if ($tenantId !== null) {
            $sql .= ' AND tenant_id = :tenant_id';
            $params[':tenant_id'] = $tenantId;
        }

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function cancel(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE {$this->table} SET status = 'cancelada' WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function cancelForTenant(int $id, int $tenantId): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table} SET status = 'cancelada' WHERE id = :id AND tenant_id = :tenant_id"
        );
        return $stmt->execute([':id' => $id, ':tenant_id' => $tenantId]);
    }

    private function bindNullableInt(\PDOStatement $stmt, string $parameter, mixed $value): void
    {
        if ($value === '' || $value === null || (int) $value === 0) {
            $stmt->bindValue($parameter, null, PDO::PARAM_NULL);
            return;
        }
        $stmt->bindValue($parameter, (int) $value, PDO::PARAM_INT);
    }

    private function bindNullableString(\PDOStatement $stmt, string $parameter, mixed $value): void
    {
        if ($value === '' || $value === null) {
            $stmt->bindValue($parameter, null, PDO::PARAM_NULL);
            return;
        }
        $stmt->bindValue($parameter, (string) $value);
    }
}
