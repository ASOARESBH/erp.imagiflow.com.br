-- Migration: 2026-08-16_saas_admin_planos_empresas.sql
-- Sistema: ERP IMAGINIFLOW
-- Objetivo: Control-plane SaaS, planos, empresas-clientes, auditoria de impersonação e tenant de controle.
-- Compatibilidade: MySQL 5.7 / HostGator compartilhado / utf8_unicode_ci.
-- Regras: somente CREATE TABLE e ADD COLUMN; sem DROP, RENAME, procedures, triggers ou events.
--
-- ATENÇÃO ANTES DE EXECUTAR EM PRODUÇÃO:
-- 1. Execute em horário de baixo tráfego e faça backup do schema.
-- 2. Confirme as colunas de tenants com SHOW COLUMNS FROM tenants;
-- 3. Caso qualquer coluna abaixo já exista, remova apenas a linha correspondente do bloco ALTER TABLE antes de executar.
-- 4. Depois do seed, defina SAAS_CONTROL_TENANT_ID no .env com o ID retornado na validação.

-- ============================================================================
-- 1. TABELAS GLOBAIS DO CONTROL-PLANE
-- ============================================================================

CREATE TABLE IF NOT EXISTS `planos` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(60) NOT NULL,
  `nome` VARCHAR(100) NOT NULL,
  `descricao` VARCHAR(255) DEFAULT NULL,
  `preco_mensal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `limite_usuarios` INT(11) DEFAULT NULL COMMENT 'NULL = ilimitado',
  `ordem` INT(11) NOT NULL DEFAULT 0,
  `status` ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_planos_slug` (`slug`),
  KEY `idx_planos_status_ordem` (`status`, `ordem`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `plano_modulos` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `plano_id` INT(11) UNSIGNED NOT NULL,
  `modulo_slug` VARCHAR(60) NOT NULL,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_plano_modulos` (`plano_id`, `modulo_slug`),
  KEY `idx_plano_modulos_plano_ativo` (`plano_id`, `ativo`),
  CONSTRAINT `fk_plano_modulos_plano`
    FOREIGN KEY (`plano_id`) REFERENCES `planos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `tenant_impersonation_logs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `saas_admin_user_id` INT(11) NOT NULL COMMENT 'Usuário do tenant de controle que iniciou a impersonação',
  `target_tenant_id` INT(11) UNSIGNED NOT NULL,
  `target_user_id` INT(11) NOT NULL COMMENT 'Usuário master materializado no tenant alvo',
  `reason` VARCHAR(255) DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ended_at` DATETIME DEFAULT NULL,
  `end_reason` VARCHAR(50) DEFAULT NULL,
  `handoff_token_hash` VARCHAR(64) DEFAULT NULL COMMENT 'Hash SHA-256 do token de entrada no tenant alvo',
  `handoff_expires_at` DATETIME DEFAULT NULL,
  `handoff_used_at` DATETIME DEFAULT NULL,
  `return_token_hash` VARCHAR(64) DEFAULT NULL COMMENT 'Hash SHA-256 do token de retorno ao control-plane',
  `return_expires_at` DATETIME DEFAULT NULL,
  `return_used_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_impersonation_target_tenant` (`target_tenant_id`),
  KEY `idx_impersonation_saas_admin` (`saas_admin_user_id`),
  KEY `idx_impersonation_open` (`ended_at`),
  KEY `idx_impersonation_handoff_token` (`handoff_token_hash`),
  KEY `idx_impersonation_return_token` (`return_token_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ============================================================================
-- 2. DADOS DE CADASTRO, PLANO E AUDITORIA EM TENANTS
-- Remova linhas de coluna que já existirem antes de aplicar a migration.
-- ============================================================================

ALTER TABLE `tenants`
  ADD COLUMN `cnpj` VARCHAR(18) DEFAULT NULL COMMENT 'CNPJ da empresa SaaS' AFTER `slug`,
  ADD COLUMN `razao_social` VARCHAR(255) DEFAULT NULL COMMENT 'Razão social da empresa' AFTER `cnpj`,
  ADD COLUMN `nome_fantasia` VARCHAR(255) DEFAULT NULL COMMENT 'Nome fantasia da empresa' AFTER `razao_social`,
  ADD COLUMN `endereco` VARCHAR(255) DEFAULT NULL COMMENT 'Logradouro' AFTER `nome_fantasia`,
  ADD COLUMN `numero` VARCHAR(20) DEFAULT NULL COMMENT 'Número do endereço' AFTER `endereco`,
  ADD COLUMN `complemento` VARCHAR(255) DEFAULT NULL COMMENT 'Complemento do endereço' AFTER `numero`,
  ADD COLUMN `bairro` VARCHAR(100) DEFAULT NULL COMMENT 'Bairro' AFTER `complemento`,
  ADD COLUMN `cidade` VARCHAR(100) DEFAULT NULL COMMENT 'Cidade' AFTER `bairro`,
  ADD COLUMN `estado` VARCHAR(2) DEFAULT NULL COMMENT 'UF' AFTER `cidade`,
  ADD COLUMN `cep` VARCHAR(10) DEFAULT NULL COMMENT 'CEP' AFTER `estado`,
  ADD COLUMN `plano_id` INT(11) UNSIGNED DEFAULT NULL COMMENT 'Plano SaaS contratado' AFTER `phone`,
  ADD COLUMN `plano_iniciado_em` DATETIME DEFAULT NULL AFTER `plano_id`,
  ADD COLUMN `trial_ends_at` DATETIME DEFAULT NULL AFTER `plano_iniciado_em`,
  ADD COLUMN `billing_email` VARCHAR(150) DEFAULT NULL AFTER `trial_ends_at`,
  ADD COLUMN `master_user_id` INT(11) DEFAULT NULL COMMENT 'Usuário master do tenant' AFTER `billing_email`,
  ADD COLUMN `created_by_saas_admin_id` INT(11) DEFAULT NULL COMMENT 'SaaS owner que criou a empresa' AFTER `master_user_id`,
  ADD COLUMN `notes` TEXT DEFAULT NULL COMMENT 'Observações internas do control-plane' AFTER `created_by_saas_admin_id`;

ALTER TABLE `tenants`
  ADD UNIQUE KEY `uq_tenants_cnpj` (`cnpj`),
  ADD KEY `idx_tenants_plano_status` (`plano_id`, `status`),
  ADD KEY `idx_tenants_master_user` (`master_user_id`);

-- ============================================================================
-- 3. PLANOS INICIAIS E MATRIZ DE MÓDULOS
-- Valores comerciais podem ser editados pelo Painel SaaS depois da ativação.
-- ============================================================================

INSERT IGNORE INTO `planos` (`slug`, `nome`, `descricao`, `preco_mensal`, `limite_usuarios`, `ordem`, `status`, `created_at`, `updated_at`) VALUES
  ('basico', 'Básico', 'Operação essencial para clínicas em início de digitalização.', 0.00, 5, 1, 'ativo', NOW(), NOW()),
  ('profissional', 'Profissional', 'Operação, financeiro e CRM para equipes em crescimento.', 0.00, 15, 2, 'ativo', NOW(), NOW()),
  ('avancado', 'Avançado', 'Recursos completos de operação, marketing e portal do cliente.', 0.00, 40, 3, 'ativo', NOW(), NOW()),
  ('enterprise', 'Enterprise', 'Todos os módulos, incluindo Hub IA e integrações avançadas.', 0.00, NULL, 4, 'ativo', NOW(), NOW());

-- Módulos básicos compartilhados por todos os planos.
INSERT IGNORE INTO `plano_modulos` (`plano_id`, `modulo_slug`, `ativo`)
SELECT p.id, m.modulo_slug, 1
FROM `planos` p
CROSS JOIN (
  SELECT 'dashboard' AS modulo_slug UNION ALL SELECT 'clientes' UNION ALL SELECT 'colaboradores'
  UNION ALL SELECT 'medicos' UNION ALL SELECT 'contratos_apuracao'
  UNION ALL SELECT 'financeiro_receber'
) m
WHERE p.slug IN ('basico', 'profissional', 'avancado', 'enterprise');

INSERT IGNORE INTO `plano_modulos` (`plano_id`, `modulo_slug`, `ativo`)
SELECT p.id, m.modulo_slug, 1
FROM `planos` p
CROSS JOIN (
  SELECT 'financeiro_pagar' AS modulo_slug UNION ALL SELECT 'financeiro_bancario'
  UNION ALL SELECT 'fornecedores' UNION ALL SELECT 'faturamento_nf'
  UNION ALL SELECT 'estoque' UNION ALL SELECT 'crm' UNION ALL SELECT 'rdv'
) m
WHERE p.slug IN ('profissional', 'avancado', 'enterprise');

INSERT IGNORE INTO `plano_modulos` (`plano_id`, `modulo_slug`, `ativo`)
SELECT p.id, m.modulo_slug, 1
FROM `planos` p
CROSS JOIN (
  SELECT 'manutencao' AS modulo_slug UNION ALL SELECT 'marketing'
  UNION ALL SELECT 'portal_cliente' UNION ALL SELECT 'cnes'
  UNION ALL SELECT 'integracoes_pagamento'
) m
WHERE p.slug IN ('avancado', 'enterprise');

INSERT IGNORE INTO `plano_modulos` (`plano_id`, `modulo_slug`, `ativo`)
SELECT p.id, m.modulo_slug, 1
FROM `planos` p
CROSS JOIN (
  SELECT 'hub_ia' AS modulo_slug
) m
WHERE p.slug = 'enterprise';

-- ============================================================================
-- 4. TENANT DE CONTROLE E SUPERADMIN INICIAL
-- O painel usa o mesmo domínio ERP compartilhado; não requer subdomínio adicional.
-- A senha inicial informada no briefing deve ser substituída imediatamente após o primeiro login.
-- ============================================================================

INSERT INTO `tenants` (`name`, `slug`, `domain`, `status`, `created_at`, `updated_at`)
SELECT 'Imagiflow SaaS Admin', 'imagiflow-saas-admin', 'saas-control.internal', 'active', NOW(), NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM `tenants` t WHERE t.slug = 'imagiflow-saas-admin'
);

SET @control_tenant_id := (
  SELECT `id` FROM `tenants` WHERE `slug` = 'imagiflow-saas-admin' LIMIT 1
);

-- Hash Argon2ID da senha inicial definida no briefing. Faça reset imediato no primeiro acesso.
INSERT INTO `users` (`name`, `email`, `role`, `status`, `password`, `two_factor_enabled`, `created_at`, `updated_at`)
SELECT 'Super Admin Imagiflow', 'master@imagiflow.com.br', 'saas_owner', 'ativo',
       '$argon2id$v=19$m=65536,t=4,p=1$MnBDOC5ZTHJqVVZycVJTaQ$Hj/f2BtYMyLTKrNuVaywoN2fWez51mQCE72cUmTfgDI',
       1, NOW(), NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM `users` u WHERE u.email = 'master@imagiflow.com.br'
);

INSERT INTO `user_tenants` (`user_id`, `tenant_id`, `role`, `status`, `is_default`, `created_at`, `updated_at`)
SELECT u.id, @control_tenant_id, 'saas_owner', 'active', 1, NOW(), NOW()
FROM `users` u
WHERE u.email = 'master@imagiflow.com.br'
  AND NOT EXISTS (
    SELECT 1 FROM `user_tenants` ut
    WHERE ut.user_id = u.id AND ut.tenant_id = @control_tenant_id
  );

-- ============================================================================
-- VALIDAÇÃO
-- ============================================================================
SHOW COLUMNS FROM `tenants`;
SHOW TABLES LIKE 'planos';
SHOW TABLES LIKE 'plano_modulos';
SHOW TABLES LIKE 'tenant_impersonation_logs';
SELECT id, name, slug, domain, status, plano_id, master_user_id
FROM tenants
ORDER BY id;
SELECT p.slug, p.nome, COUNT(pm.id) AS modulos_ativos
FROM planos p
LEFT JOIN plano_modulos pm ON pm.plano_id = p.id AND pm.ativo = 1
GROUP BY p.id, p.slug, p.nome
ORDER BY p.ordem;
