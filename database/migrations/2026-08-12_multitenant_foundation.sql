-- ============================================================================
-- Migration: Fundação multitenant do ERP Imagiflow
-- Data: 2026-08-12
-- Alvo: MySQL 5.7 / MariaDB 5.7, InnoDB, utf8 / utf8_unicode_ci
-- ============================================================================
-- IMPORTANTE: executar somente no banco de destino do Imagiflow, após backup.
-- Não executar no banco de produção do ERP InLaudo.
--
-- PRÉ-VERIFICAÇÕES MANUAIS (phpMyAdmin):
--   SHOW TABLES LIKE 'tenants';
--   SHOW TABLES LIKE 'user_tenants';
--   SHOW COLUMNS FROM users LIKE 'id';
--   Para cada tabela abaixo: SHOW COLUMNS FROM <tabela> LIKE 'tenant_id';
--   Para cada índice abaixo: SHOW INDEX FROM <tabela> WHERE Key_name = 'idx_<tabela>_tenant_id';
-- ============================================================================

CREATE TABLE IF NOT EXISTS `tenants` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(120) NOT NULL,
  `domain` VARCHAR(255) DEFAULT NULL,
  `subdomain` VARCHAR(120) DEFAULT NULL,
  `status` ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `logo` VARCHAR(300) DEFAULT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tenants_slug` (`slug`),
  UNIQUE KEY `uq_tenants_domain` (`domain`),
  UNIQUE KEY `uq_tenants_subdomain` (`subdomain`),
  KEY `idx_tenants_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `tenants` (`id`, `name`, `slug`, `domain`, `status`, `created_at`, `updated_at`)
SELECT 1, 'Imagiflow', 'imagiflow', 'erp.imagiflow.com.br', 'active', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `tenants` WHERE `slug` = 'imagiflow');

CREATE TABLE IF NOT EXISTS `user_tenants` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `tenant_id` INT(11) UNSIGNED NOT NULL,
  `role` VARCHAR(50) NOT NULL DEFAULT 'user',
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_tenants_user_tenant` (`user_id`, `tenant_id`),
  KEY `idx_user_tenants_tenant_status` (`tenant_id`, `status`),
  CONSTRAINT `fk_user_tenants_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_tenants_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Cria o vínculo do tenant inicial para usuários já existentes, sem duplicá-los.
INSERT INTO `user_tenants` (`user_id`, `tenant_id`, `role`, `status`, `is_default`, `created_at`, `updated_at`)
SELECT `id`, 1, COALESCE(`role`, 'user'), 'active', 1, NOW(), NOW()
FROM `users`
WHERE NOT EXISTS (
  SELECT 1 FROM `user_tenants` ut WHERE ut.`user_id` = `users`.`id` AND ut.`tenant_id` = 1
);

-- ============================================================================
-- COLUNAS E ÍNDICES DE ISOLAMENTO
-- Cada ALTER deve ser executado somente se a coluna/índice ainda não existir.
-- ============================================================================
ALTER TABLE `apuracao_itens` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `apuracao_itens` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `apuracao_itens` ADD INDEX `idx_apuracao_itens_tenant_id` (`tenant_id`);

ALTER TABLE `apuracoes` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `apuracoes` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `apuracoes` ADD INDEX `idx_apuracoes_tenant_id` (`tenant_id`);

ALTER TABLE `audit_logs` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `audit_logs` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `audit_logs` ADD INDEX `idx_audit_logs_tenant_id` (`tenant_id`);

ALTER TABLE `clientes` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `clientes` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `clientes` ADD INDEX `idx_clientes_tenant_id` (`tenant_id`);

ALTER TABLE `clientes_anexos` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `clientes_anexos` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `clientes_anexos` ADD INDEX `idx_clientes_anexos_tenant_id` (`tenant_id`);

ALTER TABLE `clientes_contatos` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `clientes_contatos` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `clientes_contatos` ADD INDEX `idx_clientes_contatos_tenant_id` (`tenant_id`);

ALTER TABLE `colaboradores` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `colaboradores` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `colaboradores` ADD INDEX `idx_colaboradores_tenant_id` (`tenant_id`);

ALTER TABLE `colaboradores_anexos` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `colaboradores_anexos` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `colaboradores_anexos` ADD INDEX `idx_colaboradores_anexos_tenant_id` (`tenant_id`);

ALTER TABLE `colaboradores_comissoes` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `colaboradores_comissoes` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `colaboradores_comissoes` ADD INDEX `idx_colaboradores_comissoes_tenant_id` (`tenant_id`);

ALTER TABLE `config_nfs` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `config_nfs` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `config_nfs` ADD INDEX `idx_config_nfs_tenant_id` (`tenant_id`);

ALTER TABLE `configuracoes_financeiras` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `configuracoes_financeiras` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `configuracoes_financeiras` ADD INDEX `idx_configuracoes_financeiras_tenant_id` (`tenant_id`);

ALTER TABLE `contas_bancarias` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `contas_bancarias` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `contas_bancarias` ADD INDEX `idx_contas_bancarias_tenant_id` (`tenant_id`);

ALTER TABLE `contas_movimentacoes` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `contas_movimentacoes` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `contas_movimentacoes` ADD INDEX `idx_contas_movimentacoes_tenant_id` (`tenant_id`);

ALTER TABLE `contas_pagar` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `contas_pagar` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `contas_pagar` ADD INDEX `idx_contas_pagar_tenant_id` (`tenant_id`);

ALTER TABLE `contas_pagar_anexos` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `contas_pagar_anexos` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `contas_pagar_anexos` ADD INDEX `idx_contas_pagar_anexos_tenant_id` (`tenant_id`);

ALTER TABLE `contas_receber` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `contas_receber` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `contas_receber` ADD INDEX `idx_contas_receber_tenant_id` (`tenant_id`);

ALTER TABLE `contas_receber_anexos` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `contas_receber_anexos` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `contas_receber_anexos` ADD INDEX `idx_contas_receber_anexos_tenant_id` (`tenant_id`);

ALTER TABLE `contrato_exames` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `contrato_exames` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `contrato_exames` ADD INDEX `idx_contrato_exames_tenant_id` (`tenant_id`);

ALTER TABLE `contrato_modalidades` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `contrato_modalidades` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `contrato_modalidades` ADD INDEX `idx_contrato_modalidades_tenant_id` (`tenant_id`);

ALTER TABLE `contratos` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `contratos` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `contratos` ADD INDEX `idx_contratos_tenant_id` (`tenant_id`);

ALTER TABLE `contratos_anexos` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `contratos_anexos` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `contratos_anexos` ADD INDEX `idx_contratos_anexos_tenant_id` (`tenant_id`);

ALTER TABLE `crm_anexos` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `crm_anexos` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `crm_anexos` ADD INDEX `idx_crm_anexos_tenant_id` (`tenant_id`);

ALTER TABLE `crm_interacoes` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `crm_interacoes` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `crm_interacoes` ADD INDEX `idx_crm_interacoes_tenant_id` (`tenant_id`);

ALTER TABLE `crm_leads` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `crm_leads` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `crm_leads` ADD INDEX `idx_crm_leads_tenant_id` (`tenant_id`);

ALTER TABLE `crm_oportunidade_modalidades` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `crm_oportunidade_modalidades` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `crm_oportunidade_modalidades` ADD INDEX `idx_crm_oportunidade_modalidades_tenant_id` (`tenant_id`);

ALTER TABLE `crm_oportunidades` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `crm_oportunidades` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `crm_oportunidades` ADD INDEX `idx_crm_oportunidades_tenant_id` (`tenant_id`);

ALTER TABLE `crm_proposta_aceite` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `crm_proposta_aceite` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `crm_proposta_aceite` ADD INDEX `idx_crm_proposta_aceite_tenant_id` (`tenant_id`);

ALTER TABLE `crm_proposta_historico` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `crm_proposta_historico` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `crm_proposta_historico` ADD INDEX `idx_crm_proposta_historico_tenant_id` (`tenant_id`);

ALTER TABLE `crm_proposta_itens` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `crm_proposta_itens` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `crm_proposta_itens` ADD INDEX `idx_crm_proposta_itens_tenant_id` (`tenant_id`);

ALTER TABLE `crm_propostas` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `crm_propostas` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `crm_propostas` ADD INDEX `idx_crm_propostas_tenant_id` (`tenant_id`);

ALTER TABLE `crm_transferencias` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `crm_transferencias` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `crm_transferencias` ADD INDEX `idx_crm_transferencias_tenant_id` (`tenant_id`);

ALTER TABLE `dispositivos_controlid` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `dispositivos_controlid` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `dispositivos_controlid` ADD INDEX `idx_dispositivos_controlid_tenant_id` (`tenant_id`);

ALTER TABLE `dispositivos_controlid_leituras` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `dispositivos_controlid_leituras` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `dispositivos_controlid_leituras` ADD INDEX `idx_dispositivos_controlid_leituras_tenant_id` (`tenant_id`);

ALTER TABLE `dispositivos_controlid_sync_log` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `dispositivos_controlid_sync_log` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `dispositivos_controlid_sync_log` ADD INDEX `idx_dispositivos_controlid_sync_log_tenant_id` (`tenant_id`);

ALTER TABLE `email_alertas` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `email_alertas` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `email_alertas` ADD INDEX `idx_email_alertas_tenant_id` (`tenant_id`);

ALTER TABLE `email_alertas_log` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `email_alertas_log` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `email_alertas_log` ADD INDEX `idx_email_alertas_log_tenant_id` (`tenant_id`);

ALTER TABLE `empresa_config` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `empresa_config` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `empresa_config` ADD INDEX `idx_empresa_config_tenant_id` (`tenant_id`);

ALTER TABLE `equipamentos_cliente` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `equipamentos_cliente` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `equipamentos_cliente` ADD INDEX `idx_equipamentos_cliente_tenant_id` (`tenant_id`);

ALTER TABLE `est_movimentacoes` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `est_movimentacoes` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `est_movimentacoes` ADD INDEX `idx_est_movimentacoes_tenant_id` (`tenant_id`);

ALTER TABLE `est_pedido_seq` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro';
UPDATE `est_pedido_seq` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `est_pedido_seq` ADD INDEX `idx_est_pedido_seq_tenant_id` (`tenant_id`);

ALTER TABLE `est_pedidos_compra` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `est_pedidos_compra` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `est_pedidos_compra` ADD INDEX `idx_est_pedidos_compra_tenant_id` (`tenant_id`);

ALTER TABLE `est_pedidos_compra_itens` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `est_pedidos_compra_itens` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `est_pedidos_compra_itens` ADD INDEX `idx_est_pedidos_compra_itens_tenant_id` (`tenant_id`);

ALTER TABLE `est_pedidos_venda` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `est_pedidos_venda` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `est_pedidos_venda` ADD INDEX `idx_est_pedidos_venda_tenant_id` (`tenant_id`);

ALTER TABLE `est_pedidos_venda_itens` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `est_pedidos_venda_itens` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `est_pedidos_venda_itens` ADD INDEX `idx_est_pedidos_venda_itens_tenant_id` (`tenant_id`);

ALTER TABLE `fornecedores` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `fornecedores` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `fornecedores` ADD INDEX `idx_fornecedores_tenant_id` (`tenant_id`);

ALTER TABLE `historico_importacoes_ofx` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `historico_importacoes_ofx` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `historico_importacoes_ofx` ADD INDEX `idx_historico_importacoes_ofx_tenant_id` (`tenant_id`);

ALTER TABLE `hub_ia_agente_permissoes` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `hub_ia_agente_permissoes` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `hub_ia_agente_permissoes` ADD INDEX `idx_hub_ia_agente_permissoes_tenant_id` (`tenant_id`);

ALTER TABLE `hub_ia_agentes` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `hub_ia_agentes` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `hub_ia_agentes` ADD INDEX `idx_hub_ia_agentes_tenant_id` (`tenant_id`);

ALTER TABLE `hub_ia_banco_config` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `hub_ia_banco_config` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `hub_ia_banco_config` ADD INDEX `idx_hub_ia_banco_config_tenant_id` (`tenant_id`);

ALTER TABLE `hub_ia_conectores` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `hub_ia_conectores` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `hub_ia_conectores` ADD INDEX `idx_hub_ia_conectores_tenant_id` (`tenant_id`);

ALTER TABLE `hub_ia_conhecimento_chunks` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `hub_ia_conhecimento_chunks` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `hub_ia_conhecimento_chunks` ADD INDEX `idx_hub_ia_conhecimento_chunks_tenant_id` (`tenant_id`);

ALTER TABLE `hub_ia_conhecimento_documentos` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `hub_ia_conhecimento_documentos` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `hub_ia_conhecimento_documentos` ADD INDEX `idx_hub_ia_conhecimento_documentos_tenant_id` (`tenant_id`);

ALTER TABLE `hub_ia_historico` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `hub_ia_historico` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `hub_ia_historico` ADD INDEX `idx_hub_ia_historico_tenant_id` (`tenant_id`);

ALTER TABLE `hub_ia_logs` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `hub_ia_logs` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `hub_ia_logs` ADD INDEX `idx_hub_ia_logs_tenant_id` (`tenant_id`);

ALTER TABLE `hub_ia_prompts` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `hub_ia_prompts` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `hub_ia_prompts` ADD INDEX `idx_hub_ia_prompts_tenant_id` (`tenant_id`);

ALTER TABLE `hub_ia_whatsapp_config` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `hub_ia_whatsapp_config` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `hub_ia_whatsapp_config` ADD INDEX `idx_hub_ia_whatsapp_config_tenant_id` (`tenant_id`);

ALTER TABLE `integracoes` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `integracoes` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `integracoes` ADD INDEX `idx_integracoes_tenant_id` (`tenant_id`);

ALTER TABLE `integracoes_logs` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `integracoes_logs` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `integracoes_logs` ADD INDEX `idx_integracoes_logs_tenant_id` (`tenant_id`);

ALTER TABLE `layout_exames` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `layout_exames` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `layout_exames` ADD INDEX `idx_layout_exames_tenant_id` (`tenant_id`);

ALTER TABLE `manual_historico` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `manual_historico` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `manual_historico` ADD INDEX `idx_manual_historico_tenant_id` (`tenant_id`);

ALTER TABLE `manut_ordens_servico` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `manut_ordens_servico` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `manut_ordens_servico` ADD INDEX `idx_manut_ordens_servico_tenant_id` (`tenant_id`);

ALTER TABLE `manut_os_historico` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `manut_os_historico` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `manut_os_historico` ADD INDEX `idx_manut_os_historico_tenant_id` (`tenant_id`);

ALTER TABLE `manut_os_seq` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro';
UPDATE `manut_os_seq` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `manut_os_seq` ADD INDEX `idx_manut_os_seq_tenant_id` (`tenant_id`);

ALTER TABLE `manut_os_trocas` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `manut_os_trocas` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `manut_os_trocas` ADD INDEX `idx_manut_os_trocas_tenant_id` (`tenant_id`);

ALTER TABLE `marketing_campanhas` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `marketing_campanhas` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `marketing_campanhas` ADD INDEX `idx_marketing_campanhas_tenant_id` (`tenant_id`);

ALTER TABLE `marketing_disparadores` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `marketing_disparadores` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `marketing_disparadores` ADD INDEX `idx_marketing_disparadores_tenant_id` (`tenant_id`);

ALTER TABLE `marketing_envios` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `marketing_envios` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `marketing_envios` ADD INDEX `idx_marketing_envios_tenant_id` (`tenant_id`);

ALTER TABLE `marketing_interacoes_crm` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `marketing_interacoes_crm` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `marketing_interacoes_crm` ADD INDEX `idx_marketing_interacoes_crm_tenant_id` (`tenant_id`);

ALTER TABLE `medico_crms` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `medico_crms` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `medico_crms` ADD INDEX `idx_medico_crms_tenant_id` (`tenant_id`);

ALTER TABLE `medico_exames` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `medico_exames` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `medico_exames` ADD INDEX `idx_medico_exames_tenant_id` (`tenant_id`);

ALTER TABLE `medicos` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `medicos` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `medicos` ADD INDEX `idx_medicos_tenant_id` (`tenant_id`);

ALTER TABLE `mkt_campanha_contatos` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `mkt_campanha_contatos` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `mkt_campanha_contatos` ADD INDEX `idx_mkt_campanha_contatos_tenant_id` (`tenant_id`);

ALTER TABLE `mkt_campanhas` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `mkt_campanhas` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `mkt_campanhas` ADD INDEX `idx_mkt_campanhas_tenant_id` (`tenant_id`);

ALTER TABLE `movimentacoes_bancarias` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `movimentacoes_bancarias` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `movimentacoes_bancarias` ADD INDEX `idx_movimentacoes_bancarias_tenant_id` (`tenant_id`);

ALTER TABLE `notas_fiscais` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `notas_fiscais` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `notas_fiscais` ADD INDEX `idx_notas_fiscais_tenant_id` (`tenant_id`);

ALTER TABLE `notas_fiscais_anexos` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `notas_fiscais_anexos` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `notas_fiscais_anexos` ADD INDEX `idx_notas_fiscais_anexos_tenant_id` (`tenant_id`);

ALTER TABLE `notas_fiscais_importacoes` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `notas_fiscais_importacoes` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `notas_fiscais_importacoes` ADD INDEX `idx_notas_fiscais_importacoes_tenant_id` (`tenant_id`);

ALTER TABLE `notificacao_config_alertas` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `notificacao_config_alertas` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `notificacao_config_alertas` ADD INDEX `idx_notificacao_config_alertas_tenant_id` (`tenant_id`);

ALTER TABLE `notificacoes` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `notificacoes` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `notificacoes` ADD INDEX `idx_notificacoes_tenant_id` (`tenant_id`);

ALTER TABLE `password_reset_tokens` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `password_reset_tokens` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `password_reset_tokens` ADD INDEX `idx_password_reset_tokens_tenant_id` (`tenant_id`);

ALTER TABLE `plano_contas` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `plano_contas` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `plano_contas` ADD INDEX `idx_plano_contas_tenant_id` (`tenant_id`);

ALTER TABLE `portal_clientes` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `portal_clientes` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `portal_clientes` ADD INDEX `idx_portal_clientes_tenant_id` (`tenant_id`);

ALTER TABLE `portal_clientes_tokens` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `portal_clientes_tokens` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `portal_clientes_tokens` ADD INDEX `idx_portal_clientes_tokens_tenant_id` (`tenant_id`);

ALTER TABLE `produto_codigo_seq` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro';
UPDATE `produto_codigo_seq` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `produto_codigo_seq` ADD INDEX `idx_produto_codigo_seq_tenant_id` (`tenant_id`);

ALTER TABLE `produto_comissoes` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `produto_comissoes` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `produto_comissoes` ADD INDEX `idx_produto_comissoes_tenant_id` (`tenant_id`);

ALTER TABLE `produto_componentes` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `produto_componentes` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `produto_componentes` ADD INDEX `idx_produto_componentes_tenant_id` (`tenant_id`);

ALTER TABLE `produto_historico_precos` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `produto_historico_precos` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `produto_historico_precos` ADD INDEX `idx_produto_historico_precos_tenant_id` (`tenant_id`);

ALTER TABLE `produto_lotes` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `produto_lotes` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `produto_lotes` ADD INDEX `idx_produto_lotes_tenant_id` (`tenant_id`);

ALTER TABLE `produto_movimentacoes` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `produto_movimentacoes` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `produto_movimentacoes` ADD INDEX `idx_produto_movimentacoes_tenant_id` (`tenant_id`);

ALTER TABLE `produtos` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `produtos` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `produtos` ADD INDEX `idx_produtos_tenant_id` (`tenant_id`);

ALTER TABLE `produtos_bkp_20260604` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `produtos_bkp_20260604` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `produtos_bkp_20260604` ADD INDEX `idx_produtos_bkp_20260604_tenant_id` (`tenant_id`);

ALTER TABLE `produtos_bkp_deprec_20260604` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `produtos_bkp_deprec_20260604` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `produtos_bkp_deprec_20260604` ADD INDEX `idx_produtos_bkp_deprec_20260604_tenant_id` (`tenant_id`);

ALTER TABLE `rdv_aprovacoes` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `rdv_aprovacoes` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `rdv_aprovacoes` ADD INDEX `idx_rdv_aprovacoes_tenant_id` (`tenant_id`);

ALTER TABLE `rdv_categorias` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `rdv_categorias` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `rdv_categorias` ADD INDEX `idx_rdv_categorias_tenant_id` (`tenant_id`);

ALTER TABLE `rdv_despesas` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `rdv_despesas` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `rdv_despesas` ADD INDEX `idx_rdv_despesas_tenant_id` (`tenant_id`);

ALTER TABLE `rdv_formas_pagamento` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `rdv_formas_pagamento` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `rdv_formas_pagamento` ADD INDEX `idx_rdv_formas_pagamento_tenant_id` (`tenant_id`);

ALTER TABLE `rdv_historico` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `rdv_historico` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `rdv_historico` ADD INDEX `idx_rdv_historico_tenant_id` (`tenant_id`);

ALTER TABLE `rdv_ocr_logs` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `rdv_ocr_logs` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `rdv_ocr_logs` ADD INDEX `idx_rdv_ocr_logs_tenant_id` (`tenant_id`);

ALTER TABLE `rdv_rota_clientes` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `rdv_rota_clientes` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `rdv_rota_clientes` ADD INDEX `idx_rdv_rota_clientes_tenant_id` (`tenant_id`);

ALTER TABLE `rdv_rotas` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `rdv_rotas` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `rdv_rotas` ADD INDEX `idx_rdv_rotas_tenant_id` (`tenant_id`);

ALTER TABLE `rdv_seq` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `rdv_seq` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `rdv_seq` ADD INDEX `idx_rdv_seq_tenant_id` (`tenant_id`);

ALTER TABLE `rdv_viagens` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `rdv_viagens` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `rdv_viagens` ADD INDEX `idx_rdv_viagens_tenant_id` (`tenant_id`);

ALTER TABLE `security_two_factor_logs` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `security_two_factor_logs` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `security_two_factor_logs` ADD INDEX `idx_security_two_factor_logs_tenant_id` (`tenant_id`);

ALTER TABLE `tabela_exames` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `tabela_exames` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `tabela_exames` ADD INDEX `idx_tabela_exames_tenant_id` (`tenant_id`);

ALTER TABLE `tabela_exames_tags` ADD COLUMN `tenant_id` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Tenant proprietário do registro' AFTER `id`;
UPDATE `tabela_exames_tags` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
ALTER TABLE `tabela_exames_tags` ADD INDEX `idx_tabela_exames_tags_tenant_id` (`tenant_id`);

-- `whatsapp_bot_logs` já possui `tenant_id` e o índice `idx_wbl_tenant`
-- desde a migration 2026-02-28_whatsapp_bot_integration.sql. Não reaplicar.

-- ============================================================================
-- VALIDAÇÃO
-- ============================================================================
SELECT `id`, `name`, `slug`, `domain`, `status` FROM `tenants` ORDER BY `id`;
SELECT `tenant_id`, COUNT(*) AS `usuarios_vinculados` FROM `user_tenants` GROUP BY `tenant_id`;
SELECT 'apuracao_itens' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `apuracao_itens` GROUP BY `tenant_id`;
SELECT 'apuracoes' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `apuracoes` GROUP BY `tenant_id`;
SELECT 'audit_logs' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `audit_logs` GROUP BY `tenant_id`;
SELECT 'clientes' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `clientes` GROUP BY `tenant_id`;
SELECT 'clientes_anexos' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `clientes_anexos` GROUP BY `tenant_id`;
SELECT 'clientes_contatos' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `clientes_contatos` GROUP BY `tenant_id`;
SELECT 'colaboradores' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `colaboradores` GROUP BY `tenant_id`;
SELECT 'colaboradores_anexos' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `colaboradores_anexos` GROUP BY `tenant_id`;
SELECT 'colaboradores_comissoes' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `colaboradores_comissoes` GROUP BY `tenant_id`;
SELECT 'config_nfs' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `config_nfs` GROUP BY `tenant_id`;
SELECT 'configuracoes_financeiras' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `configuracoes_financeiras` GROUP BY `tenant_id`;
SELECT 'contas_bancarias' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `contas_bancarias` GROUP BY `tenant_id`;
SELECT 'contas_movimentacoes' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `contas_movimentacoes` GROUP BY `tenant_id`;
SELECT 'contas_pagar' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `contas_pagar` GROUP BY `tenant_id`;
SELECT 'contas_pagar_anexos' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `contas_pagar_anexos` GROUP BY `tenant_id`;
SELECT 'contas_receber' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `contas_receber` GROUP BY `tenant_id`;
SELECT 'contas_receber_anexos' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `contas_receber_anexos` GROUP BY `tenant_id`;
SELECT 'contrato_exames' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `contrato_exames` GROUP BY `tenant_id`;
SELECT 'contrato_modalidades' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `contrato_modalidades` GROUP BY `tenant_id`;
SELECT 'contratos' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `contratos` GROUP BY `tenant_id`;
SELECT 'contratos_anexos' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `contratos_anexos` GROUP BY `tenant_id`;
SELECT 'crm_anexos' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `crm_anexos` GROUP BY `tenant_id`;
SELECT 'crm_interacoes' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `crm_interacoes` GROUP BY `tenant_id`;
SELECT 'crm_leads' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `crm_leads` GROUP BY `tenant_id`;
SELECT 'crm_oportunidade_modalidades' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `crm_oportunidade_modalidades` GROUP BY `tenant_id`;
SELECT 'crm_oportunidades' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `crm_oportunidades` GROUP BY `tenant_id`;
SELECT 'crm_proposta_aceite' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `crm_proposta_aceite` GROUP BY `tenant_id`;
SELECT 'crm_proposta_historico' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `crm_proposta_historico` GROUP BY `tenant_id`;
SELECT 'crm_proposta_itens' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `crm_proposta_itens` GROUP BY `tenant_id`;
SELECT 'crm_propostas' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `crm_propostas` GROUP BY `tenant_id`;
SELECT 'crm_transferencias' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `crm_transferencias` GROUP BY `tenant_id`;
SELECT 'dispositivos_controlid' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `dispositivos_controlid` GROUP BY `tenant_id`;
SELECT 'dispositivos_controlid_leituras' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `dispositivos_controlid_leituras` GROUP BY `tenant_id`;
SELECT 'dispositivos_controlid_sync_log' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `dispositivos_controlid_sync_log` GROUP BY `tenant_id`;
SELECT 'email_alertas' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `email_alertas` GROUP BY `tenant_id`;
SELECT 'email_alertas_log' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `email_alertas_log` GROUP BY `tenant_id`;
SELECT 'empresa_config' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `empresa_config` GROUP BY `tenant_id`;
SELECT 'equipamentos_cliente' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `equipamentos_cliente` GROUP BY `tenant_id`;
SELECT 'est_movimentacoes' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `est_movimentacoes` GROUP BY `tenant_id`;
SELECT 'est_pedido_seq' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `est_pedido_seq` GROUP BY `tenant_id`;
SELECT 'est_pedidos_compra' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `est_pedidos_compra` GROUP BY `tenant_id`;
SELECT 'est_pedidos_compra_itens' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `est_pedidos_compra_itens` GROUP BY `tenant_id`;
SELECT 'est_pedidos_venda' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `est_pedidos_venda` GROUP BY `tenant_id`;
SELECT 'est_pedidos_venda_itens' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `est_pedidos_venda_itens` GROUP BY `tenant_id`;
SELECT 'fornecedores' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `fornecedores` GROUP BY `tenant_id`;
SELECT 'historico_importacoes_ofx' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `historico_importacoes_ofx` GROUP BY `tenant_id`;
SELECT 'hub_ia_agente_permissoes' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `hub_ia_agente_permissoes` GROUP BY `tenant_id`;
SELECT 'hub_ia_agentes' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `hub_ia_agentes` GROUP BY `tenant_id`;
SELECT 'hub_ia_banco_config' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `hub_ia_banco_config` GROUP BY `tenant_id`;
SELECT 'hub_ia_conectores' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `hub_ia_conectores` GROUP BY `tenant_id`;
SELECT 'hub_ia_conhecimento_chunks' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `hub_ia_conhecimento_chunks` GROUP BY `tenant_id`;
SELECT 'hub_ia_conhecimento_documentos' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `hub_ia_conhecimento_documentos` GROUP BY `tenant_id`;
SELECT 'hub_ia_historico' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `hub_ia_historico` GROUP BY `tenant_id`;
SELECT 'hub_ia_logs' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `hub_ia_logs` GROUP BY `tenant_id`;
SELECT 'hub_ia_prompts' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `hub_ia_prompts` GROUP BY `tenant_id`;
SELECT 'hub_ia_whatsapp_config' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `hub_ia_whatsapp_config` GROUP BY `tenant_id`;
SELECT 'integracoes' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `integracoes` GROUP BY `tenant_id`;
SELECT 'integracoes_logs' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `integracoes_logs` GROUP BY `tenant_id`;
SELECT 'layout_exames' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `layout_exames` GROUP BY `tenant_id`;
SELECT 'manual_historico' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `manual_historico` GROUP BY `tenant_id`;
SELECT 'manut_ordens_servico' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `manut_ordens_servico` GROUP BY `tenant_id`;
SELECT 'manut_os_historico' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `manut_os_historico` GROUP BY `tenant_id`;
SELECT 'manut_os_seq' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `manut_os_seq` GROUP BY `tenant_id`;
SELECT 'manut_os_trocas' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `manut_os_trocas` GROUP BY `tenant_id`;
SELECT 'marketing_campanhas' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `marketing_campanhas` GROUP BY `tenant_id`;
SELECT 'marketing_disparadores' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `marketing_disparadores` GROUP BY `tenant_id`;
SELECT 'marketing_envios' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `marketing_envios` GROUP BY `tenant_id`;
SELECT 'marketing_interacoes_crm' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `marketing_interacoes_crm` GROUP BY `tenant_id`;
SELECT 'medico_crms' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `medico_crms` GROUP BY `tenant_id`;
SELECT 'medico_exames' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `medico_exames` GROUP BY `tenant_id`;
SELECT 'medicos' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `medicos` GROUP BY `tenant_id`;
SELECT 'mkt_campanha_contatos' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `mkt_campanha_contatos` GROUP BY `tenant_id`;
SELECT 'mkt_campanhas' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `mkt_campanhas` GROUP BY `tenant_id`;
SELECT 'movimentacoes_bancarias' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `movimentacoes_bancarias` GROUP BY `tenant_id`;
SELECT 'notas_fiscais' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `notas_fiscais` GROUP BY `tenant_id`;
SELECT 'notas_fiscais_anexos' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `notas_fiscais_anexos` GROUP BY `tenant_id`;
SELECT 'notas_fiscais_importacoes' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `notas_fiscais_importacoes` GROUP BY `tenant_id`;
SELECT 'notificacao_config_alertas' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `notificacao_config_alertas` GROUP BY `tenant_id`;
SELECT 'notificacoes' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `notificacoes` GROUP BY `tenant_id`;
SELECT 'password_reset_tokens' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `password_reset_tokens` GROUP BY `tenant_id`;
SELECT 'plano_contas' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `plano_contas` GROUP BY `tenant_id`;
SELECT 'portal_clientes' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `portal_clientes` GROUP BY `tenant_id`;
SELECT 'portal_clientes_tokens' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `portal_clientes_tokens` GROUP BY `tenant_id`;
SELECT 'produto_codigo_seq' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `produto_codigo_seq` GROUP BY `tenant_id`;
SELECT 'produto_comissoes' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `produto_comissoes` GROUP BY `tenant_id`;
SELECT 'produto_componentes' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `produto_componentes` GROUP BY `tenant_id`;
SELECT 'produto_historico_precos' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `produto_historico_precos` GROUP BY `tenant_id`;
SELECT 'produto_lotes' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `produto_lotes` GROUP BY `tenant_id`;
SELECT 'produto_movimentacoes' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `produto_movimentacoes` GROUP BY `tenant_id`;
SELECT 'produtos' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `produtos` GROUP BY `tenant_id`;
SELECT 'produtos_bkp_20260604' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `produtos_bkp_20260604` GROUP BY `tenant_id`;
SELECT 'produtos_bkp_deprec_20260604' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `produtos_bkp_deprec_20260604` GROUP BY `tenant_id`;
SELECT 'rdv_aprovacoes' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `rdv_aprovacoes` GROUP BY `tenant_id`;
SELECT 'rdv_categorias' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `rdv_categorias` GROUP BY `tenant_id`;
SELECT 'rdv_despesas' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `rdv_despesas` GROUP BY `tenant_id`;
SELECT 'rdv_formas_pagamento' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `rdv_formas_pagamento` GROUP BY `tenant_id`;
SELECT 'rdv_historico' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `rdv_historico` GROUP BY `tenant_id`;
SELECT 'rdv_ocr_logs' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `rdv_ocr_logs` GROUP BY `tenant_id`;
SELECT 'rdv_rota_clientes' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `rdv_rota_clientes` GROUP BY `tenant_id`;
SELECT 'rdv_rotas' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `rdv_rotas` GROUP BY `tenant_id`;
SELECT 'rdv_seq' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `rdv_seq` GROUP BY `tenant_id`;
SELECT 'rdv_viagens' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `rdv_viagens` GROUP BY `tenant_id`;
SELECT 'security_two_factor_logs' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `security_two_factor_logs` GROUP BY `tenant_id`;
SELECT 'tabela_exames' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `tabela_exames` GROUP BY `tenant_id`;
SELECT 'tabela_exames_tags' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `tabela_exames_tags` GROUP BY `tenant_id`;
SELECT 'whatsapp_bot_logs' AS `tabela`, `tenant_id`, COUNT(*) AS `qtd` FROM `whatsapp_bot_logs` GROUP BY `tenant_id`;

-- ============================================================================
-- ROLLBACK MANUAL (somente após validar impacto; não executar automaticamente)
-- ============================================================================
-- ALTER TABLE `whatsapp_bot_logs` DROP INDEX `idx_whatsapp_bot_logs_tenant_id`;
-- ALTER TABLE `whatsapp_bot_logs` DROP COLUMN `tenant_id`;
-- ALTER TABLE `tabela_exames_tags` DROP INDEX `idx_tabela_exames_tags_tenant_id`;
-- ALTER TABLE `tabela_exames_tags` DROP COLUMN `tenant_id`;
-- ALTER TABLE `tabela_exames` DROP INDEX `idx_tabela_exames_tenant_id`;
-- ALTER TABLE `tabela_exames` DROP COLUMN `tenant_id`;
-- ALTER TABLE `security_two_factor_logs` DROP INDEX `idx_security_two_factor_logs_tenant_id`;
-- ALTER TABLE `security_two_factor_logs` DROP COLUMN `tenant_id`;
-- ALTER TABLE `rdv_viagens` DROP INDEX `idx_rdv_viagens_tenant_id`;
-- ALTER TABLE `rdv_viagens` DROP COLUMN `tenant_id`;
-- ALTER TABLE `rdv_seq` DROP INDEX `idx_rdv_seq_tenant_id`;
-- ALTER TABLE `rdv_seq` DROP COLUMN `tenant_id`;
-- ALTER TABLE `rdv_rotas` DROP INDEX `idx_rdv_rotas_tenant_id`;
-- ALTER TABLE `rdv_rotas` DROP COLUMN `tenant_id`;
-- ALTER TABLE `rdv_rota_clientes` DROP INDEX `idx_rdv_rota_clientes_tenant_id`;
-- ALTER TABLE `rdv_rota_clientes` DROP COLUMN `tenant_id`;
-- ALTER TABLE `rdv_ocr_logs` DROP INDEX `idx_rdv_ocr_logs_tenant_id`;
-- ALTER TABLE `rdv_ocr_logs` DROP COLUMN `tenant_id`;
-- ALTER TABLE `rdv_historico` DROP INDEX `idx_rdv_historico_tenant_id`;
-- ALTER TABLE `rdv_historico` DROP COLUMN `tenant_id`;
-- ALTER TABLE `rdv_formas_pagamento` DROP INDEX `idx_rdv_formas_pagamento_tenant_id`;
-- ALTER TABLE `rdv_formas_pagamento` DROP COLUMN `tenant_id`;
-- ALTER TABLE `rdv_despesas` DROP INDEX `idx_rdv_despesas_tenant_id`;
-- ALTER TABLE `rdv_despesas` DROP COLUMN `tenant_id`;
-- ALTER TABLE `rdv_categorias` DROP INDEX `idx_rdv_categorias_tenant_id`;
-- ALTER TABLE `rdv_categorias` DROP COLUMN `tenant_id`;
-- ALTER TABLE `rdv_aprovacoes` DROP INDEX `idx_rdv_aprovacoes_tenant_id`;
-- ALTER TABLE `rdv_aprovacoes` DROP COLUMN `tenant_id`;
-- ALTER TABLE `produtos_bkp_deprec_20260604` DROP INDEX `idx_produtos_bkp_deprec_20260604_tenant_id`;
-- ALTER TABLE `produtos_bkp_deprec_20260604` DROP COLUMN `tenant_id`;
-- ALTER TABLE `produtos_bkp_20260604` DROP INDEX `idx_produtos_bkp_20260604_tenant_id`;
-- ALTER TABLE `produtos_bkp_20260604` DROP COLUMN `tenant_id`;
-- ALTER TABLE `produtos` DROP INDEX `idx_produtos_tenant_id`;
-- ALTER TABLE `produtos` DROP COLUMN `tenant_id`;
-- ALTER TABLE `produto_movimentacoes` DROP INDEX `idx_produto_movimentacoes_tenant_id`;
-- ALTER TABLE `produto_movimentacoes` DROP COLUMN `tenant_id`;
-- ALTER TABLE `produto_lotes` DROP INDEX `idx_produto_lotes_tenant_id`;
-- ALTER TABLE `produto_lotes` DROP COLUMN `tenant_id`;
-- ALTER TABLE `produto_historico_precos` DROP INDEX `idx_produto_historico_precos_tenant_id`;
-- ALTER TABLE `produto_historico_precos` DROP COLUMN `tenant_id`;
-- ALTER TABLE `produto_componentes` DROP INDEX `idx_produto_componentes_tenant_id`;
-- ALTER TABLE `produto_componentes` DROP COLUMN `tenant_id`;
-- ALTER TABLE `produto_comissoes` DROP INDEX `idx_produto_comissoes_tenant_id`;
-- ALTER TABLE `produto_comissoes` DROP COLUMN `tenant_id`;
-- ALTER TABLE `produto_codigo_seq` DROP INDEX `idx_produto_codigo_seq_tenant_id`;
-- ALTER TABLE `produto_codigo_seq` DROP COLUMN `tenant_id`;
-- ALTER TABLE `portal_clientes_tokens` DROP INDEX `idx_portal_clientes_tokens_tenant_id`;
-- ALTER TABLE `portal_clientes_tokens` DROP COLUMN `tenant_id`;
-- ALTER TABLE `portal_clientes` DROP INDEX `idx_portal_clientes_tenant_id`;
-- ALTER TABLE `portal_clientes` DROP COLUMN `tenant_id`;
-- ALTER TABLE `plano_contas` DROP INDEX `idx_plano_contas_tenant_id`;
-- ALTER TABLE `plano_contas` DROP COLUMN `tenant_id`;
-- ALTER TABLE `password_reset_tokens` DROP INDEX `idx_password_reset_tokens_tenant_id`;
-- ALTER TABLE `password_reset_tokens` DROP COLUMN `tenant_id`;
-- ALTER TABLE `notificacoes` DROP INDEX `idx_notificacoes_tenant_id`;
-- ALTER TABLE `notificacoes` DROP COLUMN `tenant_id`;
-- ALTER TABLE `notificacao_config_alertas` DROP INDEX `idx_notificacao_config_alertas_tenant_id`;
-- ALTER TABLE `notificacao_config_alertas` DROP COLUMN `tenant_id`;
-- ALTER TABLE `notas_fiscais_importacoes` DROP INDEX `idx_notas_fiscais_importacoes_tenant_id`;
-- ALTER TABLE `notas_fiscais_importacoes` DROP COLUMN `tenant_id`;
-- ALTER TABLE `notas_fiscais_anexos` DROP INDEX `idx_notas_fiscais_anexos_tenant_id`;
-- ALTER TABLE `notas_fiscais_anexos` DROP COLUMN `tenant_id`;
-- ALTER TABLE `notas_fiscais` DROP INDEX `idx_notas_fiscais_tenant_id`;
-- ALTER TABLE `notas_fiscais` DROP COLUMN `tenant_id`;
-- ALTER TABLE `movimentacoes_bancarias` DROP INDEX `idx_movimentacoes_bancarias_tenant_id`;
-- ALTER TABLE `movimentacoes_bancarias` DROP COLUMN `tenant_id`;
-- ALTER TABLE `mkt_campanhas` DROP INDEX `idx_mkt_campanhas_tenant_id`;
-- ALTER TABLE `mkt_campanhas` DROP COLUMN `tenant_id`;
-- ALTER TABLE `mkt_campanha_contatos` DROP INDEX `idx_mkt_campanha_contatos_tenant_id`;
-- ALTER TABLE `mkt_campanha_contatos` DROP COLUMN `tenant_id`;
-- ALTER TABLE `medicos` DROP INDEX `idx_medicos_tenant_id`;
-- ALTER TABLE `medicos` DROP COLUMN `tenant_id`;
-- ALTER TABLE `medico_exames` DROP INDEX `idx_medico_exames_tenant_id`;
-- ALTER TABLE `medico_exames` DROP COLUMN `tenant_id`;
-- ALTER TABLE `medico_crms` DROP INDEX `idx_medico_crms_tenant_id`;
-- ALTER TABLE `medico_crms` DROP COLUMN `tenant_id`;
-- ALTER TABLE `marketing_interacoes_crm` DROP INDEX `idx_marketing_interacoes_crm_tenant_id`;
-- ALTER TABLE `marketing_interacoes_crm` DROP COLUMN `tenant_id`;
-- ALTER TABLE `marketing_envios` DROP INDEX `idx_marketing_envios_tenant_id`;
-- ALTER TABLE `marketing_envios` DROP COLUMN `tenant_id`;
-- ALTER TABLE `marketing_disparadores` DROP INDEX `idx_marketing_disparadores_tenant_id`;
-- ALTER TABLE `marketing_disparadores` DROP COLUMN `tenant_id`;
-- ALTER TABLE `marketing_campanhas` DROP INDEX `idx_marketing_campanhas_tenant_id`;
-- ALTER TABLE `marketing_campanhas` DROP COLUMN `tenant_id`;
-- ALTER TABLE `manut_os_trocas` DROP INDEX `idx_manut_os_trocas_tenant_id`;
-- ALTER TABLE `manut_os_trocas` DROP COLUMN `tenant_id`;
-- ALTER TABLE `manut_os_seq` DROP INDEX `idx_manut_os_seq_tenant_id`;
-- ALTER TABLE `manut_os_seq` DROP COLUMN `tenant_id`;
-- ALTER TABLE `manut_os_historico` DROP INDEX `idx_manut_os_historico_tenant_id`;
-- ALTER TABLE `manut_os_historico` DROP COLUMN `tenant_id`;
-- ALTER TABLE `manut_ordens_servico` DROP INDEX `idx_manut_ordens_servico_tenant_id`;
-- ALTER TABLE `manut_ordens_servico` DROP COLUMN `tenant_id`;
-- ALTER TABLE `manual_historico` DROP INDEX `idx_manual_historico_tenant_id`;
-- ALTER TABLE `manual_historico` DROP COLUMN `tenant_id`;
-- ALTER TABLE `layout_exames` DROP INDEX `idx_layout_exames_tenant_id`;
-- ALTER TABLE `layout_exames` DROP COLUMN `tenant_id`;
-- ALTER TABLE `integracoes_logs` DROP INDEX `idx_integracoes_logs_tenant_id`;
-- ALTER TABLE `integracoes_logs` DROP COLUMN `tenant_id`;
-- ALTER TABLE `integracoes` DROP INDEX `idx_integracoes_tenant_id`;
-- ALTER TABLE `integracoes` DROP COLUMN `tenant_id`;
-- ALTER TABLE `hub_ia_whatsapp_config` DROP INDEX `idx_hub_ia_whatsapp_config_tenant_id`;
-- ALTER TABLE `hub_ia_whatsapp_config` DROP COLUMN `tenant_id`;
-- ALTER TABLE `hub_ia_prompts` DROP INDEX `idx_hub_ia_prompts_tenant_id`;
-- ALTER TABLE `hub_ia_prompts` DROP COLUMN `tenant_id`;
-- ALTER TABLE `hub_ia_logs` DROP INDEX `idx_hub_ia_logs_tenant_id`;
-- ALTER TABLE `hub_ia_logs` DROP COLUMN `tenant_id`;
-- ALTER TABLE `hub_ia_historico` DROP INDEX `idx_hub_ia_historico_tenant_id`;
-- ALTER TABLE `hub_ia_historico` DROP COLUMN `tenant_id`;
-- ALTER TABLE `hub_ia_conhecimento_documentos` DROP INDEX `idx_hub_ia_conhecimento_documentos_tenant_id`;
-- ALTER TABLE `hub_ia_conhecimento_documentos` DROP COLUMN `tenant_id`;
-- ALTER TABLE `hub_ia_conhecimento_chunks` DROP INDEX `idx_hub_ia_conhecimento_chunks_tenant_id`;
-- ALTER TABLE `hub_ia_conhecimento_chunks` DROP COLUMN `tenant_id`;
-- ALTER TABLE `hub_ia_conectores` DROP INDEX `idx_hub_ia_conectores_tenant_id`;
-- ALTER TABLE `hub_ia_conectores` DROP COLUMN `tenant_id`;
-- ALTER TABLE `hub_ia_banco_config` DROP INDEX `idx_hub_ia_banco_config_tenant_id`;
-- ALTER TABLE `hub_ia_banco_config` DROP COLUMN `tenant_id`;
-- ALTER TABLE `hub_ia_agentes` DROP INDEX `idx_hub_ia_agentes_tenant_id`;
-- ALTER TABLE `hub_ia_agentes` DROP COLUMN `tenant_id`;
-- ALTER TABLE `hub_ia_agente_permissoes` DROP INDEX `idx_hub_ia_agente_permissoes_tenant_id`;
-- ALTER TABLE `hub_ia_agente_permissoes` DROP COLUMN `tenant_id`;
-- ALTER TABLE `historico_importacoes_ofx` DROP INDEX `idx_historico_importacoes_ofx_tenant_id`;
-- ALTER TABLE `historico_importacoes_ofx` DROP COLUMN `tenant_id`;
-- ALTER TABLE `fornecedores` DROP INDEX `idx_fornecedores_tenant_id`;
-- ALTER TABLE `fornecedores` DROP COLUMN `tenant_id`;
-- ALTER TABLE `est_pedidos_venda_itens` DROP INDEX `idx_est_pedidos_venda_itens_tenant_id`;
-- ALTER TABLE `est_pedidos_venda_itens` DROP COLUMN `tenant_id`;
-- ALTER TABLE `est_pedidos_venda` DROP INDEX `idx_est_pedidos_venda_tenant_id`;
-- ALTER TABLE `est_pedidos_venda` DROP COLUMN `tenant_id`;
-- ALTER TABLE `est_pedidos_compra_itens` DROP INDEX `idx_est_pedidos_compra_itens_tenant_id`;
-- ALTER TABLE `est_pedidos_compra_itens` DROP COLUMN `tenant_id`;
-- ALTER TABLE `est_pedidos_compra` DROP INDEX `idx_est_pedidos_compra_tenant_id`;
-- ALTER TABLE `est_pedidos_compra` DROP COLUMN `tenant_id`;
-- ALTER TABLE `est_pedido_seq` DROP INDEX `idx_est_pedido_seq_tenant_id`;
-- ALTER TABLE `est_pedido_seq` DROP COLUMN `tenant_id`;
-- ALTER TABLE `est_movimentacoes` DROP INDEX `idx_est_movimentacoes_tenant_id`;
-- ALTER TABLE `est_movimentacoes` DROP COLUMN `tenant_id`;
-- ALTER TABLE `equipamentos_cliente` DROP INDEX `idx_equipamentos_cliente_tenant_id`;
-- ALTER TABLE `equipamentos_cliente` DROP COLUMN `tenant_id`;
-- ALTER TABLE `empresa_config` DROP INDEX `idx_empresa_config_tenant_id`;
-- ALTER TABLE `empresa_config` DROP COLUMN `tenant_id`;
-- ALTER TABLE `email_alertas_log` DROP INDEX `idx_email_alertas_log_tenant_id`;
-- ALTER TABLE `email_alertas_log` DROP COLUMN `tenant_id`;
-- ALTER TABLE `email_alertas` DROP INDEX `idx_email_alertas_tenant_id`;
-- ALTER TABLE `email_alertas` DROP COLUMN `tenant_id`;
-- ALTER TABLE `dispositivos_controlid_sync_log` DROP INDEX `idx_dispositivos_controlid_sync_log_tenant_id`;
-- ALTER TABLE `dispositivos_controlid_sync_log` DROP COLUMN `tenant_id`;
-- ALTER TABLE `dispositivos_controlid_leituras` DROP INDEX `idx_dispositivos_controlid_leituras_tenant_id`;
-- ALTER TABLE `dispositivos_controlid_leituras` DROP COLUMN `tenant_id`;
-- ALTER TABLE `dispositivos_controlid` DROP INDEX `idx_dispositivos_controlid_tenant_id`;
-- ALTER TABLE `dispositivos_controlid` DROP COLUMN `tenant_id`;
-- ALTER TABLE `crm_transferencias` DROP INDEX `idx_crm_transferencias_tenant_id`;
-- ALTER TABLE `crm_transferencias` DROP COLUMN `tenant_id`;
-- ALTER TABLE `crm_propostas` DROP INDEX `idx_crm_propostas_tenant_id`;
-- ALTER TABLE `crm_propostas` DROP COLUMN `tenant_id`;
-- ALTER TABLE `crm_proposta_itens` DROP INDEX `idx_crm_proposta_itens_tenant_id`;
-- ALTER TABLE `crm_proposta_itens` DROP COLUMN `tenant_id`;
-- ALTER TABLE `crm_proposta_historico` DROP INDEX `idx_crm_proposta_historico_tenant_id`;
-- ALTER TABLE `crm_proposta_historico` DROP COLUMN `tenant_id`;
-- ALTER TABLE `crm_proposta_aceite` DROP INDEX `idx_crm_proposta_aceite_tenant_id`;
-- ALTER TABLE `crm_proposta_aceite` DROP COLUMN `tenant_id`;
-- ALTER TABLE `crm_oportunidades` DROP INDEX `idx_crm_oportunidades_tenant_id`;
-- ALTER TABLE `crm_oportunidades` DROP COLUMN `tenant_id`;
-- ALTER TABLE `crm_oportunidade_modalidades` DROP INDEX `idx_crm_oportunidade_modalidades_tenant_id`;
-- ALTER TABLE `crm_oportunidade_modalidades` DROP COLUMN `tenant_id`;
-- ALTER TABLE `crm_leads` DROP INDEX `idx_crm_leads_tenant_id`;
-- ALTER TABLE `crm_leads` DROP COLUMN `tenant_id`;
-- ALTER TABLE `crm_interacoes` DROP INDEX `idx_crm_interacoes_tenant_id`;
-- ALTER TABLE `crm_interacoes` DROP COLUMN `tenant_id`;
-- ALTER TABLE `crm_anexos` DROP INDEX `idx_crm_anexos_tenant_id`;
-- ALTER TABLE `crm_anexos` DROP COLUMN `tenant_id`;
-- ALTER TABLE `contratos_anexos` DROP INDEX `idx_contratos_anexos_tenant_id`;
-- ALTER TABLE `contratos_anexos` DROP COLUMN `tenant_id`;
-- ALTER TABLE `contratos` DROP INDEX `idx_contratos_tenant_id`;
-- ALTER TABLE `contratos` DROP COLUMN `tenant_id`;
-- ALTER TABLE `contrato_modalidades` DROP INDEX `idx_contrato_modalidades_tenant_id`;
-- ALTER TABLE `contrato_modalidades` DROP COLUMN `tenant_id`;
-- ALTER TABLE `contrato_exames` DROP INDEX `idx_contrato_exames_tenant_id`;
-- ALTER TABLE `contrato_exames` DROP COLUMN `tenant_id`;
-- ALTER TABLE `contas_receber_anexos` DROP INDEX `idx_contas_receber_anexos_tenant_id`;
-- ALTER TABLE `contas_receber_anexos` DROP COLUMN `tenant_id`;
-- ALTER TABLE `contas_receber` DROP INDEX `idx_contas_receber_tenant_id`;
-- ALTER TABLE `contas_receber` DROP COLUMN `tenant_id`;
-- ALTER TABLE `contas_pagar_anexos` DROP INDEX `idx_contas_pagar_anexos_tenant_id`;
-- ALTER TABLE `contas_pagar_anexos` DROP COLUMN `tenant_id`;
-- ALTER TABLE `contas_pagar` DROP INDEX `idx_contas_pagar_tenant_id`;
-- ALTER TABLE `contas_pagar` DROP COLUMN `tenant_id`;
-- ALTER TABLE `contas_movimentacoes` DROP INDEX `idx_contas_movimentacoes_tenant_id`;
-- ALTER TABLE `contas_movimentacoes` DROP COLUMN `tenant_id`;
-- ALTER TABLE `contas_bancarias` DROP INDEX `idx_contas_bancarias_tenant_id`;
-- ALTER TABLE `contas_bancarias` DROP COLUMN `tenant_id`;
-- ALTER TABLE `configuracoes_financeiras` DROP INDEX `idx_configuracoes_financeiras_tenant_id`;
-- ALTER TABLE `configuracoes_financeiras` DROP COLUMN `tenant_id`;
-- ALTER TABLE `config_nfs` DROP INDEX `idx_config_nfs_tenant_id`;
-- ALTER TABLE `config_nfs` DROP COLUMN `tenant_id`;
-- ALTER TABLE `colaboradores_comissoes` DROP INDEX `idx_colaboradores_comissoes_tenant_id`;
-- ALTER TABLE `colaboradores_comissoes` DROP COLUMN `tenant_id`;
-- ALTER TABLE `colaboradores_anexos` DROP INDEX `idx_colaboradores_anexos_tenant_id`;
-- ALTER TABLE `colaboradores_anexos` DROP COLUMN `tenant_id`;
-- ALTER TABLE `colaboradores` DROP INDEX `idx_colaboradores_tenant_id`;
-- ALTER TABLE `colaboradores` DROP COLUMN `tenant_id`;
-- ALTER TABLE `clientes_contatos` DROP INDEX `idx_clientes_contatos_tenant_id`;
-- ALTER TABLE `clientes_contatos` DROP COLUMN `tenant_id`;
-- ALTER TABLE `clientes_anexos` DROP INDEX `idx_clientes_anexos_tenant_id`;
-- ALTER TABLE `clientes_anexos` DROP COLUMN `tenant_id`;
-- ALTER TABLE `clientes` DROP INDEX `idx_clientes_tenant_id`;
-- ALTER TABLE `clientes` DROP COLUMN `tenant_id`;
-- ALTER TABLE `audit_logs` DROP INDEX `idx_audit_logs_tenant_id`;
-- ALTER TABLE `audit_logs` DROP COLUMN `tenant_id`;
-- ALTER TABLE `apuracoes` DROP INDEX `idx_apuracoes_tenant_id`;
-- ALTER TABLE `apuracoes` DROP COLUMN `tenant_id`;
-- ALTER TABLE `apuracao_itens` DROP INDEX `idx_apuracao_itens_tenant_id`;
-- ALTER TABLE `apuracao_itens` DROP COLUMN `tenant_id`;
-- DROP TABLE `user_tenants`;
-- DELETE FROM `tenants` WHERE `slug` = 'imagiflow';
-- DROP TABLE `tenants`;
