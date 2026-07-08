-- =============================================================================
-- Migração: Log de tentativas de OCR — Módulo RDV
-- Data: 2026-07-08 | Sistema: ERP InLaudo
-- Registra cada tentativa de leitura (cliente e servidor) para auditoria/debug:
-- biblioteca usada, sucesso, confiança, tempo e erro.
-- MySQL 5.7 compatível | charset utf8 | utf8_unicode_ci
-- =============================================================================

CREATE TABLE IF NOT EXISTS `rdv_ocr_logs` (
  `id`          INT(11)      NOT NULL AUTO_INCREMENT,
  `viagem_id`   INT(11)      NOT NULL,
  `usuario_id`  INT(11)      NULL,
  `arquivo`     VARCHAR(500) NULL,
  `engine`      VARCHAR(40)  NOT NULL COMMENT 'tesseract.js, ocrspace, openai',
  `sucesso`     TINYINT(1)   NOT NULL DEFAULT 0,
  `confianca`   DECIMAL(5,2) NULL COMMENT 'percentual 0-100',
  `tempo_ms`    INT(11)      NULL,
  `erro`        TEXT         NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_rdvocrlog_viagem` (`viagem_id`),
  INDEX `idx_rdvocrlog_engine` (`engine`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ─── VALIDAÇÃO ───────────────────────────────────────────────────────────────
SHOW TABLES LIKE 'rdv_ocr_logs';
