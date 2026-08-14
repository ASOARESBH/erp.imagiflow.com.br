-- ============================================================================
-- Migração: preferência de idioma por usuário
-- Data: 2026-08-14 | ERP IMAGINIFLOW
-- Compatibilidade: MySQL 5.7 / MariaDB 5.7
-- Regra: apenas adiciona estrutura; não remove nem renomeia dados existentes.
-- ============================================================================
--
-- Antes de executar em produção:
--   SHOW COLUMNS FROM `users` LIKE 'locale';
--   Faça backup e execute em horário de baixo tráfego.
--
-- Idempotência segue o padrão da migration de 2FA já existente no projeto.
-- O portal usa a tabela portal_clientes como credencial própria; por isso recebe
-- a mesma preferência de idioma, sem alterar o isolamento existente por tenant_id.

SET @locale_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'locale'
);

SET @locale_sql := IF(
    @locale_exists = 0,
    'ALTER TABLE `users` ADD COLUMN `locale` VARCHAR(5) NOT NULL DEFAULT ''pt_BR'' COMMENT ''Idioma de interface do usuário'' AFTER `email`',
    'SELECT 1'
);

PREPARE locale_stmt FROM @locale_sql;
EXECUTE locale_stmt;
DEALLOCATE PREPARE locale_stmt;

-- Os registros históricos já recebem pt_BR pelo DEFAULT; esta atualização é explícita
-- para instalações onde a coluna tenha sido criada manualmente sem valor válido.
UPDATE `users`
SET `locale` = 'pt_BR'
WHERE `locale` IS NULL OR `locale` NOT IN ('pt_BR', 'en', 'es');

SET @portal_locale_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'portal_clientes'
      AND COLUMN_NAME = 'locale'
);

SET @portal_locale_sql := IF(
    @portal_locale_exists = 0,
    'ALTER TABLE `portal_clientes` ADD COLUMN `locale` VARCHAR(5) NOT NULL DEFAULT ''pt_BR'' COMMENT ''Idioma de interface do cliente'' AFTER `email`',
    'SELECT 1'
);

PREPARE portal_locale_stmt FROM @portal_locale_sql;
EXECUTE portal_locale_stmt;
DEALLOCATE PREPARE portal_locale_stmt;

UPDATE `portal_clientes`
SET `locale` = 'pt_BR'
WHERE `locale` IS NULL OR `locale` NOT IN ('pt_BR', 'en', 'es');

-- VALIDAÇÃO
SHOW COLUMNS FROM `users` LIKE 'locale';
SHOW COLUMNS FROM `portal_clientes` LIKE 'locale';
SELECT `locale`, COUNT(*) AS `total_usuarios`
FROM `users`
GROUP BY `locale`;
SELECT `locale`, COUNT(*) AS `total_clientes_portal`
FROM `portal_clientes`
GROUP BY `locale`;

-- ROLLBACK (executar somente se o recurso ainda não estiver em uso):
-- ALTER TABLE `portal_clientes` DROP COLUMN `locale`;
-- ALTER TABLE `users` DROP COLUMN `locale`;
