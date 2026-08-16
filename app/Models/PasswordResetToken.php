<?php

namespace App\Models;

use App\Core\Model;
use App\Core\TenantContext;
use PDO;

/**
 * Tokens de redefinição de senha.
 * Apenas o hash do token é armazenado; o valor em texto puro nunca é persistido.
 */
class PasswordResetToken extends Model
{
    protected string $table = 'password_reset_tokens';

    private const TOKEN_BYTES = 32;
    private const EXPIRY_MINUTES = 60;

    /**
     * Gera um token seguro e o associa exclusivamente ao tenant atual.
     * O valor em texto puro é usado apenas no link enviado por e-mail.
     */
    public function createForUser(int $userId): array
    {
        $rawToken = bin2hex(random_bytes(self::TOKEN_BYTES));
        $tokenHash = hash('sha256', $rawToken);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::EXPIRY_MINUTES . ' minutes'));

        $sql = "INSERT INTO {$this->table} (user_id, tenant_id, token_hash, expires_at)
                VALUES (:user_id, :tenant_id, :token_hash, :expires_at)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':tenant_id' => TenantContext::id(),
            ':token_hash' => $tokenHash,
            ':expires_at' => $expiresAt,
        ]);

        return ['raw' => $rawToken, 'hash' => $tokenHash, 'expires_at' => $expiresAt];
    }

    /**
     * Busca um token válido somente dentro do tenant atual.
     */
    public function findValidByTokenHash(string $tokenHash): object|false
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE token_hash = :token_hash
                  AND tenant_id = :tenant_id
                  AND used_at IS NULL
                  AND expires_at > NOW()
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':token_hash' => $tokenHash,
            ':tenant_id' => TenantContext::id(),
        ]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: false;
    }

    /**
     * Resolve um token ativo pelo hash sem depender do tenant do host atual.
     * O vínculo ativo usuário-tenant é exigido para impedir uso cruzado entre empresas.
     */
    public function findValidGlobalByTokenHash(string $tokenHash): object|false
    {
        $sql = "SELECT prt.*\n                FROM {$this->table} prt\n                INNER JOIN user_tenants ut\n                    ON ut.user_id = prt.user_id\n                   AND ut.tenant_id = prt.tenant_id\n                   AND ut.status = 'active'\n                INNER JOIN users u\n                    ON u.id = prt.user_id\n                   AND u.status = 'ativo'\n                INNER JOIN tenants t\n                    ON t.id = prt.tenant_id\n                   AND t.status = 'active'\n                WHERE prt.token_hash = :token_hash\n                  AND prt.used_at IS NULL\n                  AND prt.expires_at > NOW()\n                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':token_hash' => $tokenHash]);

        return $stmt->fetch(PDO::FETCH_OBJ) ?: false;
    }

    /**
     * Marca o token como utilizado somente no tenant atual.
     */
    public function markAsUsed(int $id): bool
    {
        $sql = "UPDATE {$this->table}
                SET used_at = NOW()
                WHERE id = :id
                  AND tenant_id = :tenant_id
                  AND used_at IS NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':tenant_id' => TenantContext::id(),
        ]);

        return $stmt->rowCount() === 1;
    }

    /**
     * Cria um token específico para o fluxo de criação/reset.
     */
    public function create(int $userId, string $rawToken): bool
    {
        $tokenHash = hash('sha256', $rawToken);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::EXPIRY_MINUTES . ' minutes'));

        $sql = "INSERT INTO {$this->table} (user_id, tenant_id, token_hash, expires_at)
                VALUES (:user_id, :tenant_id, :token_hash, :expires_at)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':tenant_id' => TenantContext::id(),
            ':token_hash' => $tokenHash,
            ':expires_at' => $expiresAt,
        ]);
    }

    /**
     * Invalida os tokens de um usuário somente no tenant atual.
     */
    public function invalidateUserTokens(int $userId): bool
    {
        $sql = "UPDATE {$this->table}
                SET used_at = NOW()
                WHERE user_id = :user_id
                  AND tenant_id = :tenant_id
                  AND used_at IS NULL";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':tenant_id' => TenantContext::id(),
        ]);
    }
}
