-- ============================================================
-- Migration: 2026-07-20_crm_relatorios_indices.sql
-- Módulo: CRM — Relatórios
-- Objetivo: Adicionar índices de performance para as queries
--           analíticas do módulo de Relatórios CRM.
-- Compatível com MySQL 5.7 (sem CTEs, sem Window Functions).
-- SAFE: apenas ADD INDEX — não altera dados nem estrutura.
-- ============================================================

-- ── crm_leads ────────────────────────────────────────────────
-- Índice composto para filtros por usuário + status + data
SET @idx := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'crm_leads'
      AND INDEX_NAME   = 'idx_leads_relatorio'
);
SET @sql := IF(@idx = 0,
    'ALTER TABLE crm_leads ADD INDEX idx_leads_relatorio (usuario_id, status_lead, created_at)',
    'SELECT ''idx_leads_relatorio já existe'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Índice para filtro por origem
SET @idx := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'crm_leads'
      AND INDEX_NAME   = 'idx_leads_origem'
);
SET @sql := IF(@idx = 0,
    'ALTER TABLE crm_leads ADD INDEX idx_leads_origem (origem)',
    'SELECT ''idx_leads_origem já existe'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Índice para filtro por segmento
SET @idx := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'crm_leads'
      AND INDEX_NAME   = 'idx_leads_segmento'
);
SET @sql := IF(@idx = 0,
    'ALTER TABLE crm_leads ADD INDEX idx_leads_segmento (segmento_principal)',
    'SELECT ''idx_leads_segmento já existe'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Índice para próximo contato (alertas de vencimento)
SET @idx := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'crm_leads'
      AND INDEX_NAME   = 'idx_leads_proximo_contato'
);
SET @sql := IF(@idx = 0,
    'ALTER TABLE crm_leads ADD INDEX idx_leads_proximo_contato (data_proximo_contato)',
    'SELECT ''idx_leads_proximo_contato já existe'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ── crm_oportunidades ────────────────────────────────────────
-- Índice composto para filtros por usuário + status + data
SET @idx := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'crm_oportunidades'
      AND INDEX_NAME   = 'idx_ops_relatorio'
);
SET @sql := IF(@idx = 0,
    'ALTER TABLE crm_oportunidades ADD INDEX idx_ops_relatorio (usuario_id, status_oportunidade, created_at)',
    'SELECT ''idx_ops_relatorio já existe'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Índice para etapa do funil
SET @idx := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'crm_oportunidades'
      AND INDEX_NAME   = 'idx_ops_etapa'
);
SET @sql := IF(@idx = 0,
    'ALTER TABLE crm_oportunidades ADD INDEX idx_ops_etapa (etapa_funil)',
    'SELECT ''idx_ops_etapa já existe'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Índice para tipo de contrato
SET @idx := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'crm_oportunidades'
      AND INDEX_NAME   = 'idx_ops_tipo_contrato'
);
SET @sql := IF(@idx = 0,
    'ALTER TABLE crm_oportunidades ADD INDEX idx_ops_tipo_contrato (tipo_contrato)',
    'SELECT ''idx_ops_tipo_contrato já existe'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ── crm_interacoes ───────────────────────────────────────────
-- Índice composto para filtros por usuário + tipo + data
SET @idx := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'crm_interacoes'
      AND INDEX_NAME   = 'idx_int_relatorio'
);
SET @sql := IF(@idx = 0,
    'ALTER TABLE crm_interacoes ADD INDEX idx_int_relatorio (usuario_id, tipo_interacao, data_interacao)',
    'SELECT ''idx_int_relatorio já existe'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Índice para related_type + related_id (subqueries de contagem)
SET @idx := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'crm_interacoes'
      AND INDEX_NAME   = 'idx_int_related'
);
SET @sql := IF(@idx = 0,
    'ALTER TABLE crm_interacoes ADD INDEX idx_int_related (related_type, related_id)',
    'SELECT ''idx_int_related já existe'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- Fim da migration 2026-07-20_crm_relatorios_indices.sql
-- ============================================================
