-- ============================================================
-- Migration: Módulo Pagamento DDA (Débito Direto Autorizado)
-- Data: 2026-07-03
-- Tabela: dda_boletos
-- Integração: Asaas API - /v3/bill-payments
-- ============================================================

CREATE TABLE IF NOT EXISTS `dda_boletos` (
    `id`                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuario_id`            INT UNSIGNED NOT NULL COMMENT 'Tenant (usuário dono da conta Asaas)',

    -- Dados do Asaas
    `asaas_id`              VARCHAR(60)  NOT NULL COMMENT 'ID do boleto no Asaas (bill_xxxxxxxx)',
    `asaas_status`          VARCHAR(30)  NOT NULL DEFAULT 'PENDING' COMMENT 'Status Asaas: PENDING, BANK_PROCESSING, PAID, CANCELLED, FAILED',

    -- Dados do beneficiário (quem vai receber o pagamento)
    `beneficiario_nome`     VARCHAR(200) DEFAULT NULL,
    `beneficiario_cpf_cnpj` VARCHAR(20)  DEFAULT NULL,
    `beneficiario_banco`    VARCHAR(100) DEFAULT NULL,

    -- Dados do boleto
    `codigo_barras`         VARCHAR(100) DEFAULT NULL COMMENT 'Código de barras do boleto',
    `linha_digitavel`       VARCHAR(100) DEFAULT NULL COMMENT 'Linha digitável',
    `valor`                 DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `valor_desconto`        DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `valor_juros`           DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `valor_multa`           DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `valor_final`           DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Valor a pagar após descontos/juros',
    `data_vencimento`       DATE         NOT NULL,
    `data_pagamento`        DATE         DEFAULT NULL COMMENT 'Data efetiva do pagamento',
    `data_limite_pagamento` DATE         DEFAULT NULL COMMENT 'Data limite para pagamento com desconto',

    -- Dados de importação/vinculação interna
    `status_interno`        ENUM('pendente','importado','pago_asaas','pago_inlaudo','cancelado','ignorado')
                            NOT NULL DEFAULT 'pendente'
                            COMMENT 'Status interno: pendente=só no Asaas, importado=vinculado a conta_pagar, pago_*=confirmado',
    `conta_pagar_id`        INT UNSIGNED DEFAULT NULL COMMENT 'Vinculação com contas_pagar ao importar',
    `descricao`             VARCHAR(300) DEFAULT NULL COMMENT 'Descrição/observação do boleto',
    `observacao`            TEXT         DEFAULT NULL COMMENT 'Observações adicionais ao importar',

    -- Controle
    `importado_em`          DATETIME     DEFAULT NULL COMMENT 'Quando foi importado para contas_pagar',
    `pago_em`               DATETIME     DEFAULT NULL COMMENT 'Quando foi confirmado o pagamento',
    `pago_por`              VARCHAR(20)  DEFAULT NULL COMMENT 'asaas ou inlaudo',
    `asaas_raw`             TEXT         DEFAULT NULL COMMENT 'JSON completo retornado pelo Asaas',
    `created_at`            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_asaas_id_usuario` (`asaas_id`, `usuario_id`),
    KEY `idx_usuario_status` (`usuario_id`, `status_interno`),
    KEY `idx_vencimento` (`data_vencimento`),
    KEY `idx_conta_pagar` (`conta_pagar_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
  COMMENT='Boletos recebidos via DDA do Asaas para pagamento';
