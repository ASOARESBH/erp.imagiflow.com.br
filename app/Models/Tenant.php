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
               AND (LOWER(domain) = :domain_host OR LOWER(subdomain) = :subdomain_host)
             LIMIT 1"
        );
        $stmt->execute([
            ':domain_host' => $host,
            ':subdomain_host' => $host,
        ]);

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

    /**
     * Resolve o tenant de controle SaaS pelo slug estável. Usado somente como
     * fallback quando SAAS_CONTROL_TENANT_ID ainda não foi configurado.
     */
    public function findControlTenantId(): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT id FROM {$this->table} WHERE slug = :slug AND status = 'active' LIMIT 1"
        );
        $stmt->execute([':slug' => 'imagiflow-saas-admin']);

        return (int) $stmt->fetchColumn();
    }

    public function findActiveById(int $id): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table} WHERE id = :id AND status = 'active' LIMIT 1"
        );
        $stmt->execute([':id' => $id]);

        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Resolve um tenant ativo somente quando há vínculo ativo para o usuário.
     * Usado exclusivamente pelo host compartilhado após autenticação ou handoff interno.
     */
    public function findActiveForUser(int $tenantId, int $userId): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT t.*\n             FROM {$this->table} t\n             INNER JOIN user_tenants ut ON ut.tenant_id = t.id\n             WHERE t.id = :tenant_id\n               AND t.status = 'active'\n               AND ut.user_id = :user_id\n               AND ut.status = 'active'\n             LIMIT 1"
        );
        $stmt->execute([':tenant_id' => $tenantId, ':user_id' => $userId]);

        return $stmt->fetch(PDO::FETCH_OBJ) ?: false;
    }

    /**
     * Métodos abaixo são exclusivos do control-plane, protegido por SaasAdminMiddleware.
     * A tabela tenants é global por definição arquitetural.
     */
    public function findById(int $id): object|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);

        return $stmt->fetch(PDO::FETCH_OBJ) ?: false;
    }

    public function listAllWithPlan(): array
    {
        $stmt = $this->pdo->query(
            "SELECT t.*, p.nome AS plano_nome, p.slug AS plano_slug,
                    u.name AS master_user_name, u.email AS master_user_email
             FROM {$this->table} t
             LEFT JOIN planos p ON p.id = t.plano_id
             LEFT JOIN users u ON u.id = t.master_user_id
             ORDER BY t.created_at DESC, t.id DESC"
        );

        return $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];
    }

    public function cnpjExists(string $cnpj, ?int $ignoreId = null): bool
    {
        $sql = "SELECT 1 FROM {$this->table} WHERE cnpj = :cnpj";
        $params = [':cnpj' => $cnpj];
        if ($ignoreId !== null) {
            $sql .= ' AND id <> :id';
            $params[':id'] = $ignoreId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    public function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $sql = "SELECT 1 FROM {$this->table} WHERE slug = :slug";
        $params = [':slug' => strtolower(trim($slug))];
        if ($ignoreId !== null) {
            $sql .= ' AND id <> :id';
            $params[':id'] = $ignoreId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    public function createSaas(array $data): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->table}
             (name, slug, domain, subdomain, status, email, phone, cnpj, razao_social,
              nome_fantasia, endereco, numero, complemento, bairro, cidade, estado, cep,
              plano_id, plano_iniciado_em, trial_ends_at, billing_email,
              created_by_saas_admin_id, notes, created_at, updated_at)
             VALUES
             (:name, :slug, :domain, :subdomain, :status, :email, :phone, :cnpj, :razao_social,
              :nome_fantasia, :endereco, :numero, :complemento, :bairro, :cidade, :estado, :cep,
              :plano_id, :plano_iniciado_em, :trial_ends_at, :billing_email,
              :created_by_saas_admin_id, :notes, NOW(), NOW())"
        );
        $stmt->execute([
            ':name' => $data['name'],
            ':slug' => $data['slug'],
            ':domain' => $data['domain'],
            ':subdomain' => $data['subdomain'],
            ':status' => $data['status'],
            ':email' => $data['email'],
            ':phone' => $data['phone'],
            ':cnpj' => $data['cnpj'],
            ':razao_social' => $data['razao_social'],
            ':nome_fantasia' => $data['nome_fantasia'],
            ':endereco' => $data['endereco'],
            ':numero' => $data['numero'],
            ':complemento' => $data['complemento'],
            ':bairro' => $data['bairro'],
            ':cidade' => $data['cidade'],
            ':estado' => $data['estado'],
            ':cep' => $data['cep'],
            ':plano_id' => $data['plano_id'],
            ':plano_iniciado_em' => $data['plano_iniciado_em'],
            ':trial_ends_at' => $data['trial_ends_at'],
            ':billing_email' => $data['billing_email'],
            ':created_by_saas_admin_id' => $data['created_by_saas_admin_id'],
            ':notes' => $data['notes'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateSaas(int $id, array $data): bool
    {
        $params = [
            ':id' => $id,
            ':name' => $data['name'], ':slug' => $data['slug'], ':domain' => $data['domain'],
            ':subdomain' => $data['subdomain'], ':status' => $data['status'], ':email' => $data['email'],
            ':phone' => $data['phone'], ':cnpj' => $data['cnpj'], ':razao_social' => $data['razao_social'],
            ':nome_fantasia' => $data['nome_fantasia'], ':endereco' => $data['endereco'],
            ':numero' => $data['numero'], ':complemento' => $data['complemento'], ':bairro' => $data['bairro'],
            ':cidade' => $data['cidade'], ':estado' => $data['estado'], ':cep' => $data['cep'],
            ':plano_id' => $data['plano_id'], ':plano_iniciado_em' => $data['plano_iniciado_em'],
            ':trial_ends_at' => $data['trial_ends_at'], ':billing_email' => $data['billing_email'],
            ':notes' => $data['notes'],
        ];
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table}
             SET name = :name, slug = :slug, domain = :domain, subdomain = :subdomain,
                 status = :status, email = :email, phone = :phone, cnpj = :cnpj,
                 razao_social = :razao_social, nome_fantasia = :nome_fantasia,
                 endereco = :endereco, numero = :numero, complemento = :complemento,
                 bairro = :bairro, cidade = :cidade, estado = :estado, cep = :cep,
                 plano_id = :plano_id, plano_iniciado_em = :plano_iniciado_em,
                 trial_ends_at = :trial_ends_at, billing_email = :billing_email,
                 notes = :notes, updated_at = NOW()
             WHERE id = :id"
        );

        return $stmt->execute($params);
    }

    /**
     * Atualiza somente os dados corporativos do tenant. O status, plano, slug e
     * domínio permanecem sob controle exclusivo do painel SaaS.
     */
    public function updateCompanyProfile(int $tenantId, array $data): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table}
             SET name = :name,
                 email = :email,
                 phone = :phone,
                 cnpj = :cnpj,
                 razao_social = :razao_social,
                 nome_fantasia = :nome_fantasia,
                 endereco = :endereco,
                 numero = :numero,
                 complemento = :complemento,
                 bairro = :bairro,
                 cidade = :cidade,
                 estado = :estado,
                 cep = :cep,
                 billing_email = :billing_email,
                 logo = COALESCE(:logo, logo),
                 updated_at = NOW()
             WHERE id = :id
               AND status = 'active'"
        );

        return $stmt->execute([
            ':id' => $tenantId,
            ':name' => $data['name'],
            ':email' => $data['email'],
            ':phone' => $data['phone'],
            ':cnpj' => $data['cnpj'],
            ':razao_social' => $data['razao_social'],
            ':nome_fantasia' => $data['nome_fantasia'],
            ':endereco' => $data['endereco'],
            ':numero' => $data['numero'],
            ':complemento' => $data['complemento'],
            ':bairro' => $data['bairro'],
            ':cidade' => $data['cidade'],
            ':estado' => $data['estado'],
            ':cep' => $data['cep'],
            ':billing_email' => $data['billing_email'],
            ':logo' => $data['logo'] ?? null,
        ]);
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table} SET status = :status, updated_at = NOW() WHERE id = :id"
        );

        return $stmt->execute([':id' => $id, ':status' => $status]);
    }

    public function setMasterUser(int $tenantId, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table} SET master_user_id = :user_id, updated_at = NOW() WHERE id = :id"
        );

        return $stmt->execute([':id' => $tenantId, ':user_id' => $userId]);
    }
}
