-- =============================================================================
-- Migração: Módulo RDV — Registro de Despesas de Viagem
-- Data: 2026-07-07 | Sistema: ERP InLaudo
-- Lógica: Viagem → Rota (pode ter vários clientes/leads) → Despesas
--         Rota Livre = sem controle de clientes/leads
-- MySQL 5.7 compatível | charset utf8 | utf8_unicode_ci
-- =============================================================================

-- ─── 1. CATEGORIAS DE DESPESA ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `rdv_categorias` (
  `id`          INT(11)      NOT NULL AUTO_INCREMENT,
  `usuario_id`  INT(11)      NOT NULL COMMENT 'Dono da categoria (0 = sistema/global)',
  `nome`        VARCHAR(100) NOT NULL,
  `icone`       VARCHAR(50)  NULL DEFAULT 'fa-receipt',
  `cor`         VARCHAR(20)  NULL DEFAULT '#6b7280',
  `ativo`       TINYINT(1)   NOT NULL DEFAULT 1,
  `sistema`     TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1 = categoria padrão do sistema',
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_rdvcat_usuario` (`usuario_id`),
  INDEX `idx_rdvcat_ativo`   (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ─── 2. FORMAS DE PAGAMENTO ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `rdv_formas_pagamento` (
  `id`         INT(11)     NOT NULL AUTO_INCREMENT,
  `nome`       VARCHAR(80) NOT NULL,
  `ativo`      TINYINT(1)  NOT NULL DEFAULT 1,
  `sistema`    TINYINT(1)  NOT NULL DEFAULT 0,
  `created_at` DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ─── 3. ROTAS COMERCIAIS ─────────────────────────────────────────────────────
-- Uma rota agrupa múltiplos clientes/leads que serão visitados numa viagem.
-- Rota Livre (tipo = 'livre') não controla clientes — apenas registra despesas.
CREATE TABLE IF NOT EXISTS `rdv_rotas` (
  `id`          INT(11)      NOT NULL AUTO_INCREMENT,
  `usuario_id`  INT(11)      NOT NULL COMMENT 'Criador da rota',
  `nome`        VARCHAR(200) NOT NULL COMMENT 'Ex: Triângulo Mineiro, Sul de MG',
  `descricao`   TEXT         NULL,
  `tipo`        ENUM('padrao','livre') NOT NULL DEFAULT 'padrao'
                COMMENT 'padrao = com clientes; livre = sem controle',
  `regiao`      VARCHAR(100) NULL COMMENT 'Região geográfica',
  `estado`      CHAR(2)      NULL,
  `ativo`       TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_rdvrota_usuario` (`usuario_id`),
  INDEX `idx_rdvrota_ativo`   (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ─── 4. CLIENTES/LEADS DE UMA ROTA ───────────────────────────────────────────
-- Associa clientes e/ou leads a uma rota comercial.
CREATE TABLE IF NOT EXISTS `rdv_rota_clientes` (
  `id`              INT(11)  NOT NULL AUTO_INCREMENT,
  `rota_id`         INT(11)  NOT NULL,
  `cliente_id`      INT(11)  NULL COMMENT 'FK clientes.id (pode ser null se for lead)',
  `lead_id`         INT(11)  NULL COMMENT 'FK crm_leads.id (pode ser null se for cliente)',
  `oportunidade_id` INT(11)  NULL COMMENT 'FK crm_oportunidades.id (opcional)',
  `ordem`           INT(11)  NOT NULL DEFAULT 0 COMMENT 'Ordem de visita sugerida',
  `observacoes`     VARCHAR(500) NULL,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_rdvrc_rota`    (`rota_id`),
  INDEX `idx_rdvrc_cliente` (`cliente_id`),
  INDEX `idx_rdvrc_lead`    (`lead_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ─── 5. VIAGENS ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `rdv_viagens` (
  `id`             INT(11)       NOT NULL AUTO_INCREMENT,
  `usuario_id`     INT(11)       NOT NULL COMMENT 'Vendedor responsável',
  `rota_id`        INT(11)       NULL     COMMENT 'Rota comercial associada (pode ser null)',
  `codigo`         VARCHAR(30)   NOT NULL COMMENT 'Ex: RDV-2026-00001',
  `nome`           VARCHAR(200)  NOT NULL,
  `status`         ENUM('aberto','iniciado','concluido','cancelado') NOT NULL DEFAULT 'aberto',
  `periodo_inicio` DATE          NOT NULL,
  `periodo_fim`    DATE          NOT NULL,
  `motivo`         TEXT          NULL,
  `cidade`         VARCHAR(100)  NULL,
  `estado`         CHAR(2)       NULL,
  `pais`           VARCHAR(60)   NULL DEFAULT 'Brasil',
  `valor_previsto` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `valor_real`     DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `observacoes`    TEXT          NULL,
  `aprovacao_status` ENUM('pendente','aprovado','reprovado') NULL DEFAULT NULL,
  `aprovado_por`   INT(11)       NULL,
  `aprovado_em`    DATETIME      NULL,
  `conta_pagar_id` INT(11)       NULL COMMENT 'FK contas_pagar.id após integração financeira',
  `created_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME      NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_rdv_codigo` (`codigo`),
  INDEX `idx_rdv_usuario`  (`usuario_id`),
  INDEX `idx_rdv_rota`     (`rota_id`),
  INDEX `idx_rdv_status`   (`status`),
  INDEX `idx_rdv_periodo`  (`periodo_inicio`, `periodo_fim`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ─── 6. SEQUENCIAL DE VIAGENS ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `rdv_seq` (
  `id`            INT(11) NOT NULL AUTO_INCREMENT,
  `usuario_id`    INT(11) NOT NULL,
  `ano`           INT(4)  NOT NULL,
  `ultimo_numero` INT(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_rdvseq_usuario_ano` (`usuario_id`, `ano`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ─── 7. DESPESAS ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `rdv_despesas` (
  `id`               INT(11)       NOT NULL AUTO_INCREMENT,
  `viagem_id`        INT(11)       NOT NULL,
  `categoria_id`     INT(11)       NULL,
  `forma_pagamento_id` INT(11)     NULL,
  `descricao`        VARCHAR(500)  NOT NULL,
  `valor`            DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `data_documento`   DATE          NULL,
  `numero_documento` VARCHAR(100)  NULL,
  `fornecedor`       VARCHAR(200)  NULL,
  `cnpj_fornecedor`  VARCHAR(20)   NULL,
  `cidade`           VARCHAR(100)  NULL,
  `hora_documento`   TIME          NULL,
  `arquivo`          VARCHAR(500)  NULL COMMENT 'Caminho do comprovante',
  `ocr_json`         TEXT          NULL COMMENT 'JSON retornado pelo OCR',
  `ocr_status`       ENUM('pendente','processando','concluido','erro') NULL DEFAULT NULL,
  `fora_periodo`     TINYINT(1)    NOT NULL DEFAULT 0 COMMENT '1 = despesa fora do período autorizado',
  `log_fora_periodo` TEXT          NULL,
  `tipo`             ENUM('simples','completa') NOT NULL DEFAULT 'simples',
  `created_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME      NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_rdvdesp_viagem`    (`viagem_id`),
  INDEX `idx_rdvdesp_categoria` (`categoria_id`),
  INDEX `idx_rdvdesp_data`      (`data_documento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ─── 8. HISTÓRICO / TIMELINE DA VIAGEM ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS `rdv_historico` (
  `id`              INT(11)      NOT NULL AUTO_INCREMENT,
  `viagem_id`       INT(11)      NOT NULL,
  `usuario_id`      INT(11)      NOT NULL,
  `usuario_nome`    VARCHAR(255) NULL,
  `tipo`            VARCHAR(50)  NOT NULL COMMENT 'status_change, despesa_add, aprovacao, etc.',
  `descricao`       TEXT         NOT NULL,
  `dados_extras`    TEXT         NULL COMMENT 'JSON com dados adicionais',
  `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_rdvhist_viagem` (`viagem_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ─── 9. APROVAÇÕES ───────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `rdv_aprovacoes` (
  `id`          INT(11)      NOT NULL AUTO_INCREMENT,
  `viagem_id`   INT(11)      NOT NULL,
  `usuario_id`  INT(11)      NOT NULL COMMENT 'Aprovador',
  `status`      ENUM('pendente','aprovado','reprovado') NOT NULL DEFAULT 'pendente',
  `observacao`  TEXT         NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_rdvaprov_viagem`  (`viagem_id`),
  INDEX `idx_rdvaprov_usuario` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ─── 10. SEED: Categorias padrão do sistema ──────────────────────────────────
INSERT IGNORE INTO `rdv_categorias` (`usuario_id`, `nome`, `icone`, `cor`, `sistema`) VALUES
(0, 'Alimentação',         'fa-utensils',        '#f59e0b', 1),
(0, 'Combustível',         'fa-gas-pump',         '#ef4444', 1),
(0, 'Uber',                'fa-car',              '#1d4ed8', 1),
(0, '99',                  'fa-car-side',         '#7c3aed', 1),
(0, 'Táxi',                'fa-taxi',             '#f97316', 1),
(0, 'Pedágio',             'fa-road',             '#64748b', 1),
(0, 'Hospedagem',          'fa-bed',              '#0891b2', 1),
(0, 'Hotel',               'fa-hotel',            '#0e7490', 1),
(0, 'Passagem Aérea',      'fa-plane',            '#6366f1', 1),
(0, 'Passagem Rodoviária', 'fa-bus',              '#8b5cf6', 1),
(0, 'Estacionamento',      'fa-parking',          '#475569', 1),
(0, 'Lavanderia',          'fa-tshirt',           '#14b8a6', 1),
(0, 'Farmácia',            'fa-pills',            '#10b981', 1),
(0, 'Internet',            'fa-wifi',             '#3b82f6', 1),
(0, 'Telefone',            'fa-phone',            '#22c55e', 1),
(0, 'Material',            'fa-box',              '#a16207', 1),
(0, 'Representação',       'fa-handshake',        '#dc2626', 1),
(0, 'Brinde',              'fa-gift',             '#ec4899', 1),
(0, 'Outros',              'fa-ellipsis-h',       '#94a3b8', 1);

-- ─── 11. SEED: Formas de pagamento ───────────────────────────────────────────
INSERT IGNORE INTO `rdv_formas_pagamento` (`nome`, `sistema`) VALUES
('Dinheiro',            1),
('PIX',                 1),
('Cartão Corporativo',  1),
('Cartão Pessoal',      1),
('Débito',              1),
('Crédito',             1),
('Transferência',       1),
('Boleto',              1);

-- ─── VALIDAÇÃO ───────────────────────────────────────────────────────────────
SHOW TABLES LIKE 'rdv_%';
SELECT COUNT(*) AS total_categorias FROM rdv_categorias;
SELECT COUNT(*) AS total_formas     FROM rdv_formas_pagamento;
