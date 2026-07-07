-- =============================================================================
-- Migração: Garantir existência e integridade da tabela manut_os_trocas
-- Data: 2026-07-07 | Sistema: ERP InLaudo
-- Motivo: Correção do erro "Falha ao adicionar item de troca" — a tabela pode
--         não ter sido criada ou pode estar com estrutura desatualizada.
-- =============================================================================

-- ─── PASSO 1: Criar tabela se não existir ────────────────────────────────────
CREATE TABLE IF NOT EXISTS `manut_os_trocas` (
  `id`                    INT(11)       NOT NULL AUTO_INCREMENT,
  `os_id`                 INT(11)       NOT NULL COMMENT 'FK manut_ordens_servico.id',
  `produto_id`            INT(11)       NULL     COMMENT 'FK produtos.id',
  `produto_codigo`        VARCHAR(50)   NULL,
  `descricao`             VARCHAR(500)  NOT NULL COMMENT 'O que foi trocado/feito',
  `unidade`               VARCHAR(20)   NULL DEFAULT 'UN',
  `quantidade`            DECIMAL(10,3) NOT NULL DEFAULT 1.000,
  `preco_unitario`        DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `preco_total`           DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `vida_util_meses`       INT(11)       NULL     COMMENT 'Vida útil da peça trocada',
  `data_proxima_troca`    DATE          NULL     COMMENT 'Calculada: data_conclusao + vida_util_meses',
  `observacoes`           VARCHAR(500)  NULL,
  `created_at`            DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ─── PASSO 2: Adicionar índices se não existirem ─────────────────────────────
-- (MySQL 5.7 não suporta CREATE INDEX IF NOT EXISTS — usar procedure ou ignorar erro)
ALTER TABLE `manut_os_trocas`
  ADD INDEX `idx_trocas_os` (`os_id`);

ALTER TABLE `manut_os_trocas`
  ADD INDEX `idx_trocas_produto` (`produto_id`);

-- ─── PASSO 3: Garantir que manut_ordens_servico tem os campos de totais ──────
ALTER TABLE `manut_ordens_servico`
  ADD COLUMN IF NOT EXISTS `valor_pecas`  DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `valor_servico`;

ALTER TABLE `manut_ordens_servico`
  ADD COLUMN IF NOT EXISTS `valor_total`  DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `valor_pecas`;

-- ─── PASSO 4: Garantir que manut_os_seq existe ───────────────────────────────
CREATE TABLE IF NOT EXISTS `manut_os_seq` (
  `id`            INT(11)  NOT NULL AUTO_INCREMENT,
  `usuario_id`    INT(11)  NOT NULL,
  `ano`           INT(4)   NOT NULL,
  `ultimo_numero` INT(11)  NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_seq_usuario_ano` (`usuario_id`, `ano`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ─── PASSO 5: Garantir que manut_os_historico existe ────────────────────────
CREATE TABLE IF NOT EXISTS `manut_os_historico` (
  `id`              INT(11)       NOT NULL AUTO_INCREMENT,
  `os_id`           INT(11)       NOT NULL,
  `usuario_id`      INT(11)       NOT NULL,
  `usuario_nome`    VARCHAR(255)  NULL,
  `status_anterior` VARCHAR(50)   NULL,
  `status_novo`     VARCHAR(50)   NULL,
  `descricao`       TEXT          NOT NULL,
  `created_at`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_oshist_os` (`os_id`),
  INDEX `idx_oshist_usuario` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ─── VALIDAÇÃO ───────────────────────────────────────────────────────────────
SHOW TABLES LIKE 'manut_os_trocas';
SHOW COLUMNS FROM `manut_os_trocas`;
SELECT COUNT(*) AS total_trocas FROM `manut_os_trocas`;
