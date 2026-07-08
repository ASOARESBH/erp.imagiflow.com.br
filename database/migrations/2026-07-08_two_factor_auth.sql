-- Migration: Autenticação em Dois Fatores (2FA) por e-mail
-- Date: 2026-07-08
-- Rules: ONLY CREATE TABLE / ADD COLUMN. Never drop/rename existing columns.
--
-- Observação de segurança: a coluna two_factor_code armazena o HASH SHA-256
-- do código de 4 dígitos, nunca o valor em texto puro (mesma disciplina já
-- usada em password_reset_tokens.token_hash). Por isso o tipo foi ajustado
-- de VARCHAR(10) para VARCHAR(64) (tamanho de um hash SHA-256 em hex).

-- ─── 1. Colunas de 2FA na tabela users ───────────────────────────────────────
ALTER TABLE users ADD COLUMN IF NOT EXISTS two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER status;
ALTER TABLE users ADD COLUMN IF NOT EXISTS two_factor_code VARCHAR(64) NULL DEFAULT NULL AFTER two_factor_enabled;
ALTER TABLE users ADD COLUMN IF NOT EXISTS two_factor_expiration DATETIME NULL DEFAULT NULL AFTER two_factor_code;
ALTER TABLE users ADD COLUMN IF NOT EXISTS two_factor_attempts INT(11) NOT NULL DEFAULT 0 AFTER two_factor_expiration;
ALTER TABLE users ADD COLUMN IF NOT EXISTS two_factor_last_sent DATETIME NULL DEFAULT NULL AFTER two_factor_attempts;
ALTER TABLE users ADD COLUMN IF NOT EXISTS two_factor_validated TINYINT(1) NOT NULL DEFAULT 0 AFTER two_factor_last_sent;
-- Coluna adicional (não pedida explicitamente, mas necessária para a ETAPA 9
-- "conta temporariamente bloqueada" após 5 tentativas incorretas):
ALTER TABLE users ADD COLUMN IF NOT EXISTS two_factor_locked_until DATETIME NULL DEFAULT NULL AFTER two_factor_validated;

-- ─── 2. Log de segurança do 2FA (auditoria) ──────────────────────────────────
CREATE TABLE IF NOT EXISTS security_two_factor_logs (
  id          INT(11)      NOT NULL AUTO_INCREMENT,
  user_id     INT(11)      NULL,
  email       VARCHAR(190) NULL,
  ip_address  VARCHAR(45)  NULL,
  user_agent  VARCHAR(255) NULL,
  os          VARCHAR(100) NULL,
  browser     VARCHAR(100) NULL,
  action      ENUM('code_sent','verify_success','verify_failed','resend','locked','enabled','disabled') NOT NULL,
  attempts    INT(11)      NOT NULL DEFAULT 0,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_2fa_log_user   (user_id),
  INDEX idx_2fa_log_action (action),
  INDEX idx_2fa_log_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── VALIDAÇÃO ───────────────────────────────────────────────────────────────
SHOW COLUMNS FROM users LIKE 'two_factor%';
SHOW TABLES LIKE 'security_two_factor_logs';
