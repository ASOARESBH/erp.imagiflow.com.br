<?php

namespace App\Models;

use App\Core\Logger;
use App\Core\Model;
use App\Core\TenantContext;
use PDO;

class User extends Model
{
    protected string $table = "users";

    public function findById(int $id): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT u.*, ut.tenant_id, ut.role AS tenant_role
             FROM {$this->table} u
             INNER JOIN user_tenants ut ON ut.user_id = u.id
             WHERE u.id = :id
               AND ut.tenant_id = :tenant_id
               AND ut.status = 'active'
             LIMIT 1"
        );
        $stmt->execute([
            ':id' => $id,
            ':tenant_id' => TenantContext::id(),
        ]);
        return $stmt->fetch();
    }

    /**
     * Encontra um usuário pelo seu endereço de e-mail.
     *
     * @param string $email O e-mail do usuário.
     * @return object|false O objeto do usuário se encontrado, ou false caso contrário.
     */
    public function findByEmail(string $email): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT u.*, ut.tenant_id, ut.role AS tenant_role
             FROM {$this->table} u
             INNER JOIN user_tenants ut ON ut.user_id = u.id
             WHERE u.email = :email
               AND ut.tenant_id = :tenant_id
               AND ut.status = 'active'
             LIMIT 1"
        );
        $stmt->execute([
            ':email' => $email,
            ':tenant_id' => TenantContext::id(),
        ]);
        return $stmt->fetch();
    }

    /**
     * Cria um novo usuário no banco de dados.
     *
     * @param array $data Os dados do usuário (ex: ["name" => ..., "email" => ..., "password" => ...]).
     * @return string|false O ID do usuário inserido, ou false em caso de falha.
     */
    public function create(array $data): string|false
    {
        $tenantId = TenantContext::id();
        $role = $data['role'] ?? 'user';
        $this->assertSaasOwnerAssignment((string) $role, $tenantId);

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare(
                "INSERT INTO {$this->table} (name, email, password, role)
                 VALUES (:name, :email, :password, :role)"
            );
            $stmt->execute([
                ':name' => $data['name'],
                ':email' => $data['email'],
                ':password' => $data['password'],
                ':role' => $role,
            ]);

            $userId = (int) $this->pdo->lastInsertId();
            $link = $this->pdo->prepare(
                "INSERT INTO user_tenants (user_id, tenant_id, role, status, is_default)
                 VALUES (:user_id, :tenant_id, :role, 'active', 1)"
            );
            $link->execute([
                ':user_id' => $userId,
                ':tenant_id' => $tenantId,
                ':role' => $role,
            ]);

            $this->pdo->commit();
            return (string) $userId;
        } catch (\PDOException $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            (new Logger())->error('Falha ao criar usuário e vínculo de tenant', [
                'tenant_id' => $tenantId,
                'email' => $data['email'] ?? null,
                'error' => $exception->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Atualiza a preferência de idioma do usuário no tenant atual.
     */
    public function updateLocale(int $userId, string $locale): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table}
             SET locale = :locale, updated_at = NOW()
             WHERE id = :id
               AND EXISTS (
                 SELECT 1 FROM user_tenants ut
                 WHERE ut.user_id = {$this->table}.id
                   AND ut.tenant_id = :tenant_id
                   AND ut.status = 'active'
               )"
        );

        return $stmt->execute([
            ':locale' => $locale,
            ':id' => $userId,
            ':tenant_id' => TenantContext::id(),
        ]);
    }

    /**
     * Atualiza a senha do usuário (hash seguro).
     */
    public function updatePassword(int $userId, string $hashedPassword): bool
    {
        $sql = "UPDATE {$this->table}
                SET password = :password, updated_at = NOW()
                WHERE id = :id
                  AND EXISTS (
                    SELECT 1 FROM user_tenants ut
                    WHERE ut.user_id = {$this->table}.id
                      AND ut.tenant_id = :tenant_id
                      AND ut.status = 'active'
                  )";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':password' => $hashedPassword,
            ':id' => $userId,
            ':tenant_id' => TenantContext::id(),
        ]);
    }

    /**
     * Atualiza os dados de um usuário (nome, e-mail, role, status).
     *
     * @param int    $id     ID do usuário a atualizar.
     * @param array  $data   Campos a atualizar: name, email, role, status.
     * @return bool  True em caso de sucesso, false em caso de falha.
     */
    public function update(int $id, array $data): bool
    {
        try {
            $this->assertSaasOwnerAssignment((string) ($data['role'] ?? ''), TenantContext::id());
            // Verifica se a coluna status existe na tabela users
            $checkCol = $this->pdo->query("SHOW COLUMNS FROM {$this->table} LIKE 'status'");
            $statusExists = $checkCol && $checkCol->rowCount() > 0;

            if ($statusExists) {
                $sql = "UPDATE {$this->table}
                        SET name = :name,
                            email = :email,
                            role = :role,
                            status = :status,
                            updated_at = NOW()
                        WHERE id = :id
                          AND EXISTS (
                            SELECT 1 FROM user_tenants ut
                            WHERE ut.user_id = {$this->table}.id
                              AND ut.tenant_id = :tenant_id
                              AND ut.status = 'active'
                          )";
                $params = [
                    ':name'   => $data['name'],
                    ':email'  => $data['email'],
                    ':role'   => $data['role'],
                    ':status' => $data['status'] ?? 'ativo',
                    ':id'     => $id,
                    ':tenant_id' => TenantContext::id(),
                ];
            } else {
                // Coluna status não existe ainda — atualiza sem ela
                $sql = "UPDATE {$this->table}
                        SET name = :name,
                            email = :email,
                            role = :role,
                            updated_at = NOW()
                        WHERE id = :id
                          AND EXISTS (
                            SELECT 1 FROM user_tenants ut
                            WHERE ut.user_id = {$this->table}.id
                              AND ut.tenant_id = :tenant_id
                              AND ut.status = 'active'
                          )";
                $params = [
                    ':name'  => $data['name'],
                    ':email' => $data['email'],
                    ':role'  => $data['role'],
                    ':id'    => $id,
                    ':tenant_id' => TenantContext::id(),
                ];

            }

            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);

            if ($result && array_key_exists('role', $data)) {
                $roleStmt = $this->pdo->prepare(
                    "UPDATE user_tenants
                     SET role = :role, updated_at = NOW()
                     WHERE user_id = :user_id
                       AND tenant_id = :tenant_id
                       AND status = 'active'"
                );
                $result = $roleStmt->execute([
                    ':role' => $data['role'],
                    ':user_id' => $id,
                    ':tenant_id' => TenantContext::id(),
                ]);
            }

            if (!$result) {
                (new Logger())->error('Falha ao atualizar usuário no tenant', [
                    'user_id' => $id,
                    'tenant_id' => TenantContext::id(),
                    'error' => $stmt->errorInfo(),
                ]);
            }

            return $result;
        } catch (\PDOException $e) {
            (new Logger())->error('Exceção ao atualizar usuário no tenant', [
                'user_id' => $id,
                'tenant_id' => TenantContext::id(),
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * O papel saas_owner pertence exclusivamente ao tenant de controle.
     * Esta validação central protege fluxos de criação e edição de usuários.
     */
    private function assertSaasOwnerAssignment(string $role, int $tenantId): void
    {
        if ($role !== 'saas_owner') {
            return;
        }
        $controlTenantId = (int) ($_ENV['SAAS_CONTROL_TENANT_ID'] ?? 0);
        if ($controlTenantId <= 0 || $tenantId !== $controlTenantId) {
            throw new \LogicException('O papel saas_owner só pode ser atribuído no tenant de controle SaaS.');
        }
    }

    // =========================================================
    // AUTENTICAÇÃO EM DOIS FATORES (2FA)
    // =========================================================

    /**
     * Habilita/desabilita o 2FA para o usuário.
     */
    public function setTwoFactorEnabled(int $id, bool $enabled): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table}
             SET two_factor_enabled = :enabled, updated_at = NOW()
             WHERE id = :id
               AND EXISTS (
                 SELECT 1 FROM user_tenants ut
                 WHERE ut.user_id = {$this->table}.id
                   AND ut.tenant_id = :tenant_id
                   AND ut.status = 'active'
               )"
        );
        return $stmt->execute([
            ':enabled' => $enabled ? 1 : 0,
            ':id' => $id,
            ':tenant_id' => TenantContext::id(),
        ]);
    }

    /**
     * Salva um novo código de 2FA (já com hash — nunca texto puro) e reinicia
     * tentativas/bloqueio. Também registra o horário de envio (cooldown de reenvio).
     */
    public function saveTwoFactorCode(int $id, string $codeHash, string $expiresAt): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table}
             SET two_factor_code = :code,
                 two_factor_expiration = :exp,
                 two_factor_attempts = 0,
                 two_factor_validated = 0,
                 two_factor_last_sent = NOW(),
                 two_factor_locked_until = NULL
             WHERE id = :id
               AND EXISTS (
                 SELECT 1 FROM user_tenants ut
                 WHERE ut.user_id = {$this->table}.id
                   AND ut.tenant_id = :tenant_id
                   AND ut.status = 'active'
               )"
        );
        return $stmt->execute([
            ':code' => $codeHash,
            ':exp' => $expiresAt,
            ':id' => $id,
            ':tenant_id' => TenantContext::id(),
        ]);
    }

    /**
     * Incrementa o contador de tentativas incorretas e retorna o novo total.
     */
    public function incrementTwoFactorAttempts(int $id): int
    {
        $this->pdo->prepare(
            "UPDATE {$this->table}
             SET two_factor_attempts = two_factor_attempts + 1
             WHERE id = :id
               AND EXISTS (
                 SELECT 1 FROM user_tenants ut
                 WHERE ut.user_id = {$this->table}.id
                   AND ut.tenant_id = :tenant_id
                   AND ut.status = 'active'
               )"
        )->execute([
            ':id' => $id,
            ':tenant_id' => TenantContext::id(),
        ]);

        $row = $this->findById($id);
        return (int) ($row->two_factor_attempts ?? 0);
    }

    /**
     * Bloqueia temporariamente a verificação de 2FA (após exceder tentativas).
     */
    public function lockTwoFactor(int $id, string $lockedUntil): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table}
             SET two_factor_locked_until = :until
             WHERE id = :id
               AND EXISTS (
                 SELECT 1 FROM user_tenants ut
                 WHERE ut.user_id = {$this->table}.id
                   AND ut.tenant_id = :tenant_id
                   AND ut.status = 'active'
               )"
        );
        return $stmt->execute([
            ':until' => $lockedUntil,
            ':id' => $id,
            ':tenant_id' => TenantContext::id(),
        ]);
    }

    /**
     * Marca o código como validado e o invalida para reuso (nunca reutilizável).
     */
    public function markTwoFactorValidated(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table}
             SET two_factor_validated = 1,
                 two_factor_code = NULL,
                 two_factor_expiration = NULL,
                 two_factor_attempts = 0,
                 two_factor_locked_until = NULL
             WHERE id = :id
               AND EXISTS (
                 SELECT 1 FROM user_tenants ut
                 WHERE ut.user_id = {$this->table}.id
                   AND ut.tenant_id = :tenant_id
                   AND ut.status = 'active'
               )"
        );
        return $stmt->execute([
            ':id' => $id,
            ':tenant_id' => TenantContext::id(),
        ]);
    }

    /**
     * Retorna todos os usuários do sistema
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT u.*, ut.tenant_id, ut.role AS tenant_role
             FROM {$this->table} u
             INNER JOIN user_tenants ut ON ut.user_id = u.id
             WHERE ut.tenant_id = :tenant_id
               AND ut.status = 'active'
             ORDER BY u.created_at DESC"
        );
        $stmt->execute([':tenant_id' => TenantContext::id()]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Retorna usuários com um ou mais roles específicos.
     * Usado pelo EmailAlertaService para resolver destinatários 'admin'/'financeiro'.
     *
     * @param string|array $roles  Ex: 'admin' ou ['admin', 'superadmin']
     */
    public function findByRole(string|array $roles): array
    {
        $roles        = (array) $roles;
        $placeholders = implode(',', array_fill(0, count($roles), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT u.*, ut.tenant_id, ut.role AS tenant_role
             FROM {$this->table} u
             INNER JOIN user_tenants ut ON ut.user_id = u.id
             WHERE ut.tenant_id = ?
               AND ut.status = 'active'
               AND ut.role IN ({$placeholders})
             ORDER BY u.name ASC"
        );
        $stmt->execute(array_merge([TenantContext::id()], $roles));
        return $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];
    }
}
