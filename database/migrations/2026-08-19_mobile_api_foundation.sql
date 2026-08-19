-- Migration: 2026-08-19_mobile_api_foundation
-- Date: 2026-08-19
-- System: ERP Imagiflow
-- Rules: ONLY CREATE TABLE / ADD COLUMN. Never drop or rename existing columns.
-- Compatibility: MySQL 5.7 / cPanel shared hosting
--
-- Antes de executar em produção:
-- 1. Faça backup do banco de dados.
-- 2. Execute em horário de baixo tráfego.
-- 3. Confirme o resultado da seção VALIDAÇÃO ao final.

-- Tokens opacos de autenticação para o aplicativo móvel.
-- O token em texto nunca é persistido; somente seu SHA-256.
CREATE TABLE IF NOT EXISTS `api_tokens` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT(11) UNSIGNED NOT NULL,
  `user_id` INT(11) NOT NULL,
  `token_hash` CHAR(64) NOT NULL COMMENT 'SHA-256 do token; o token puro nunca e armazenado',
  `device_name` VARCHAR(120) DEFAULT NULL,
  `device_platform` ENUM('ios','android') DEFAULT NULL,
  `push_token` VARCHAR(255) DEFAULT NULL COMMENT 'Token FCM/APNs do dispositivo',
  `last_used_at` DATETIME DEFAULT NULL,
  `expires_at` DATETIME DEFAULT NULL,
  `revoked_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_api_tokens_hash` (`token_hash`),
  KEY `idx_api_tokens_tenant` (`tenant_id`),
  KEY `idx_api_tokens_user` (`user_id`),
  KEY `idx_api_tokens_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tokens opacos revogaveis para API mobile';

-- Desafio temporário, de uso único, que permite 2FA mobile sem sessão PHP/cookie.
CREATE TABLE IF NOT EXISTS `mobile_auth_challenges` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT(11) UNSIGNED NOT NULL,
  `user_id` INT(11) NOT NULL,
  `challenge_hash` CHAR(64) NOT NULL COMMENT 'SHA-256 do challenge token',
  `device_name` VARCHAR(120) DEFAULT NULL,
  `device_platform` ENUM('ios','android') DEFAULT NULL,
  `expires_at` DATETIME NOT NULL,
  `consumed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_mobile_auth_challenges_hash` (`challenge_hash`),
  KEY `idx_mobile_auth_challenges_lookup` (`tenant_id`, `user_id`, `expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Desafios temporarios de segundo fator para app mobile';

-- Registro de tentativas para limitar brute force no login mobile por IP e e-mail.
CREATE TABLE IF NOT EXISTS `mobile_login_attempts` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT(11) UNSIGNED NOT NULL,
  `email_hash` CHAR(64) NOT NULL COMMENT 'SHA-256 do e-mail normalizado',
  `ip_address` VARCHAR(45) NOT NULL,
  `attempted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `success` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_mobile_login_attempts_rate` (`tenant_id`, `email_hash`, `ip_address`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Rate limit auditavel de login mobile';

-- Localização pontual vinculada a uma ação de campo. Não realiza rastreamento contínuo.
CREATE TABLE IF NOT EXISTS `colaboradores_localizacoes` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT(11) UNSIGNED NOT NULL,
  `user_id` INT(11) NOT NULL,
  `colaborador_id` INT(11) DEFAULT NULL COMMENT 'FK lógica para colaboradores quando houver vinculo',
  `latitude` DECIMAL(10,7) NOT NULL,
  `longitude` DECIMAL(10,7) NOT NULL,
  `accuracy_meters` DECIMAL(6,2) DEFAULT NULL,
  `contexto` ENUM('cliente_create','cliente_update','crm_interacao','rdv_visita','check_in_manual') NOT NULL,
  `referencia_tabela` VARCHAR(60) DEFAULT NULL,
  `referencia_id` INT(11) DEFAULT NULL,
  `captured_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_colab_loc_tenant_captured` (`tenant_id`, `captured_at`),
  KEY `idx_colab_loc_user_captured` (`user_id`, `captured_at`),
  KEY `idx_colab_loc_reference` (`referencia_tabela`, `referencia_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Localizacoes pontuais de colaboradores para operacao em campo';

-- Foto de perfil isolada por tenant, evitando modificar a tabela global users.
CREATE TABLE IF NOT EXISTS `user_profile_avatars` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT(11) UNSIGNED NOT NULL,
  `user_id` INT(11) NOT NULL,
  `path` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_profile_avatars_tenant_user` (`tenant_id`, `user_id`),
  KEY `idx_user_profile_avatars_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Fotos de perfil por usuario e tenant';

-- VALIDAÇÃO
SHOW TABLES LIKE 'api_tokens';
SHOW TABLES LIKE 'mobile_auth_challenges';
SHOW TABLES LIKE 'mobile_login_attempts';
SHOW TABLES LIKE 'colaboradores_localizacoes';
SHOW TABLES LIKE 'user_profile_avatars';
SHOW COLUMNS FROM `api_tokens`;
SHOW COLUMNS FROM `mobile_auth_challenges`;
SHOW COLUMNS FROM `mobile_login_attempts`;
SHOW COLUMNS FROM `colaboradores_localizacoes`;
SHOW INDEX FROM `api_tokens`;
SHOW INDEX FROM `colaboradores_localizacoes`;

-- ROLLBACK
-- Esta migration cria estruturas novas. Não execute DROP TABLE automaticamente em produção.
-- Caso seja necessário reverter, isole a decisão com backup aprovado e execute manualmente
-- apenas após confirmar que não existem dados operacionais a preservar.
