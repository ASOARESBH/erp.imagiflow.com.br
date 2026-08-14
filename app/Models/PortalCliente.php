<?php

namespace App\Models;

use App\Core\Model;
use App\Core\TenantContext;
use PDO;

/**
 * Model para gerenciar as credenciais de acesso do Portal do Cliente.
 * Tabela: portal_clientes
 */
class PortalCliente extends Model
{
    protected string $table = 'portal_clientes';

    /**
     * Busca o registro do portal pelo e-mail.
     */
    public function findByEmail(string $email): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT pc.*, c.razao_social, c.nome_fantasia, c.cpf_cnpj, c.email AS email_principal,
                    c.telefone, c.celular, c.cidade, c.estado, pc.tenant_id,
                    c.endereco, c.numero, c.complemento, c.bairro, c.cep
             FROM {$this->table} pc
             INNER JOIN clientes c ON c.id = pc.cliente_id AND c.tenant_id = pc.tenant_id
             WHERE pc.email = :email
               AND pc.ativo = 1
               AND pc.tenant_id = :tenant_id
             LIMIT 1"
        );
        $stmt->execute([
            ':email' => strtolower(trim($email)),
            ':tenant_id' => TenantContext::id(),
        ]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: false;
    }

    /**
     * Busca o registro do portal pelo cliente_id.
     */
    public function findByClienteId(int $clienteId): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table} WHERE cliente_id = :cliente_id AND tenant_id = :tenant_id LIMIT 1"
        );
        $stmt->execute([
            ':cliente_id' => $clienteId,
            ':tenant_id' => TenantContext::id(),
        ]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: false;
    }

    /**
     * Busca pelo ID do portal com dados completos do cliente.
     */
    public function findById(int $id): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT pc.*, c.razao_social, c.nome_fantasia, c.cpf_cnpj, c.email AS email_principal,
                    c.telefone, c.celular, c.cidade, c.estado, pc.tenant_id,
                    c.endereco, c.numero, c.complemento, c.bairro, c.cep
             FROM {$this->table} pc
             INNER JOIN clientes c ON c.id = pc.cliente_id AND c.tenant_id = pc.tenant_id
             WHERE pc.id = :id
               AND pc.ativo = 1
               AND pc.tenant_id = :tenant_id
             LIMIT 1"
        );
        $stmt->execute([
            ':id' => $id,
            ':tenant_id' => TenantContext::id(),
        ]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: false;
    }

    /**
     * Cria ou atualiza o acesso ao portal para um cliente.
     */
    public function upsert(int $clienteId, string $email): bool
    {
        $email = strtolower(trim($email));
        $existing = $this->findByClienteId($clienteId);

        if ($existing) {
            $stmt = $this->pdo->prepare(
                "UPDATE {$this->table}
                 SET email = :email, updated_at = NOW()
                 WHERE cliente_id = :cliente_id AND tenant_id = :tenant_id"
            );
            return $stmt->execute([
                ':email' => $email,
                ':cliente_id' => $clienteId,
                ':tenant_id' => TenantContext::id(),
            ]);
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->table} (cliente_id, tenant_id, email, password_hash, primeiro_acesso, ativo)
             VALUES (:cliente_id, :tenant_id, :email, NULL, 1, 1)"
        );
        return $stmt->execute([
            ':cliente_id' => $clienteId,
            ':tenant_id' => TenantContext::id(),
            ':email' => $email,
        ]);
    }

    /**
     * Define a senha do portal (após o primeiro acesso).
     */
    public function definirSenha(int $id, string $passwordHash): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table}
             SET password_hash = :password_hash, primeiro_acesso = 0, updated_at = NOW()
             WHERE id = :id AND tenant_id = :tenant_id"
        );
        return $stmt->execute([
            ':password_hash' => $passwordHash,
            ':id' => $id,
            ':tenant_id' => TenantContext::id(),
        ]);
    }

    /**
     * Atualiza o timestamp de último acesso.
     */
    public function registrarAcesso(int $id): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table}
             SET ultimo_acesso = NOW()
             WHERE id = :id AND tenant_id = :tenant_id"
        );
        $stmt->execute([
            ':id' => $id,
            ':tenant_id' => TenantContext::id(),
        ]);
    }

    /**
     * Atualiza a preferência de idioma do cliente autenticado no tenant atual.
     */
    public function updateLocale(int $id, string $locale): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table}
             SET locale = :locale, updated_at = NOW()
             WHERE id = :id AND tenant_id = :tenant_id"
        );

        return $stmt->execute([
            ':locale' => $locale,
            ':id' => $id,
            ':tenant_id' => TenantContext::id(),
        ]);
    }

    /**
     * Cria um token de primeiro acesso ou reset de senha.
     * Retorna o token gerado.
     */
    public function criarToken(int $clienteId, string $tipo = 'primeiro_acesso'): string
    {
        // Invalida tokens anteriores do mesmo tipo
        $stmt = $this->pdo->prepare(
            "UPDATE portal_clientes_tokens
             SET usado = 1
             WHERE cliente_id = :cliente_id
               AND tenant_id = :tenant_id
               AND tipo = :tipo
               AND usado = 0"
        );
        $stmt->execute([
            ':cliente_id' => $clienteId,
            ':tenant_id' => TenantContext::id(),
            ':tipo' => $tipo,
        ]);

        $token = bin2hex(random_bytes(48)); // 96 chars hex
        $expira = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $stmt = $this->pdo->prepare(
            "INSERT INTO portal_clientes_tokens (cliente_id, tenant_id, token, tipo, expira_em)
             VALUES (:cliente_id, :tenant_id, :token, :tipo, :expira_em)"
        );
        $stmt->execute([
            ':cliente_id' => $clienteId,
            ':tenant_id' => TenantContext::id(),
            ':token' => $token,
            ':tipo' => $tipo,
            ':expira_em' => $expira,
        ]);

        return $token;
    }

    /**
     * Valida e retorna um token (deve estar ativo e não expirado).
     */
    public function validarToken(string $token, string $tipo = 'primeiro_acesso'): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT t.*, pc.id AS portal_id, pc.email, pc.primeiro_acesso, pc.cliente_id
             FROM portal_clientes_tokens t
             INNER JOIN portal_clientes pc ON pc.cliente_id = t.cliente_id AND pc.tenant_id = t.tenant_id
             WHERE t.token = :token
               AND t.tipo = :tipo
               AND t.tenant_id = :tenant_id
               AND t.usado = 0
               AND t.expira_em > NOW()
             LIMIT 1"
        );
        $stmt->execute([
            ':token' => $token,
            ':tipo' => $tipo,
            ':tenant_id' => TenantContext::id(),
        ]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: false;
    }

    /**
     * Marca um token como usado.
     */
    public function consumirToken(string $token): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE portal_clientes_tokens
             SET usado = 1
             WHERE token = :token AND tenant_id = :tenant_id"
        );
        $stmt->execute([
            ':token' => $token,
            ':tenant_id' => TenantContext::id(),
        ]);
    }
}
