-- ============================================================================
-- Migration: Parcelas recorrentes em contas a pagar
-- Data: 2026-08-18
-- Alvo: MySQL 5.7 / MariaDB 5.7, HostGator, utf8/utf8_unicode_ci
-- ============================================================================
-- OBJETIVO
-- Permitir que uma conta recorrente de prazo definido gere todas as parcelas
-- futuras a partir do vencimento inicial, mantendo grupo e numeração.
--
-- IMPORTANTE
-- 1. Faça backup antes de executar.
-- 2. Execute cada ALTER somente se a coluna/índice ainda NÃO existir.
-- 3. Não há comandos destrutivos nesta migration.
--
-- PRÉ-VERIFICAÇÕES (phpMyAdmin)
-- SHOW COLUMNS FROM contas_pagar LIKE 'recorrencia_modo';
-- SHOW COLUMNS FROM contas_pagar LIKE 'numero_parcela';
-- SHOW COLUMNS FROM contas_pagar LIKE 'total_parcelas';
-- SHOW COLUMNS FROM contas_pagar LIKE 'grupo_parcelas';
-- SHOW INDEX FROM contas_pagar WHERE Key_name = 'idx_cp_grupo_parcelas';
-- ============================================================================

ALTER TABLE contas_pagar
  ADD COLUMN recorrencia_modo VARCHAR(20) NULL DEFAULT NULL COMMENT 'rolling ou antecipado' AFTER recorrencia_intervalo,
  ADD COLUMN numero_parcela SMALLINT UNSIGNED NULL DEFAULT NULL COMMENT 'Número da parcela' AFTER recorrencia_modo,
  ADD COLUMN total_parcelas SMALLINT UNSIGNED NULL DEFAULT NULL COMMENT 'Total de parcelas do grupo' AFTER numero_parcela,
  ADD COLUMN grupo_parcelas VARCHAR(64) NULL DEFAULT NULL COMMENT 'Identificador do grupo de parcelas' AFTER total_parcelas;

ALTER TABLE contas_pagar
  ADD INDEX idx_cp_grupo_parcelas (tenant_id, grupo_parcelas);

-- VALIDAÇÃO
SELECT
  COUNT(*) AS total_contas,
  SUM(CASE WHEN grupo_parcelas IS NOT NULL THEN 1 ELSE 0 END) AS contas_com_grupo
FROM contas_pagar;

SELECT tenant_id, grupo_parcelas, COUNT(*) AS parcelas
FROM contas_pagar
WHERE grupo_parcelas IS NOT NULL
GROUP BY tenant_id, grupo_parcelas
ORDER BY parcelas DESC, grupo_parcelas ASC
LIMIT 50;

-- ROLLBACK (executar somente se a aplicação nova ainda não estiver em uso)
-- ALTER TABLE contas_pagar DROP INDEX idx_cp_grupo_parcelas;
-- ALTER TABLE contas_pagar
--   DROP COLUMN grupo_parcelas,
--   DROP COLUMN total_parcelas,
--   DROP COLUMN numero_parcela,
--   DROP COLUMN recorrencia_modo;
