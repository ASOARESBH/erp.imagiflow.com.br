-- ============================================================
-- Migração: Sistema de Notificações e Configurações de Alertas
-- Data: 2026-06-25 | Sistema: ERP inlaudo
-- Tabelas: notificacoes, notificacao_config_alertas
-- ============================================================

-- ------------------------------------------------------------
-- 1. Tabela principal de notificações (inbox por usuário)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notificacoes (
    id              INT(11)      NOT NULL AUTO_INCREMENT,
    usuario_id      INT(11)      NOT NULL                    COMMENT 'Usuário destinatário',
    tipo            VARCHAR(60)  NOT NULL                    COMMENT 'Código do tipo: crm_retorno_vencendo, conta_pagar_vencendo, etc.',
    titulo          VARCHAR(200) NOT NULL                    COMMENT 'Título curto da notificação',
    mensagem        TEXT         NULL                        COMMENT 'Texto completo da notificação',
    link            VARCHAR(500) NULL                        COMMENT 'URL de destino ao clicar',
    icone           VARCHAR(80)  NULL DEFAULT 'fas fa-bell'  COMMENT 'Classe FontAwesome do ícone',
    cor             VARCHAR(20)  NULL DEFAULT 'primary'      COMMENT 'Cor Bootstrap: primary, warning, danger, success, info',
    lida            TINYINT(1)   NOT NULL DEFAULT 0          COMMENT '0=não lida, 1=lida',
    lida_em         DATETIME     NULL                        COMMENT 'Quando foi marcada como lida',
    referencia_tipo VARCHAR(60)  NULL                        COMMENT 'Tipo do objeto de referência: oportunidade, conta_pagar, etc.',
    referencia_id   INT(11)      NULL                        COMMENT 'ID do objeto de referência',
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Notificações do sistema por usuário';

-- ------------------------------------------------------------
-- 2. Tabela de configuração de alertas por usuário
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notificacao_config_alertas (
    id              INT(11)      NOT NULL AUTO_INCREMENT,
    usuario_id      INT(11)      NOT NULL                    COMMENT 'Usuário proprietário da configuração',
    tipo            VARCHAR(60)  NOT NULL                    COMMENT 'Código do tipo de alerta',
    ativo           TINYINT(1)   NOT NULL DEFAULT 1          COMMENT '1=ativo, 0=desativado',
    dias_antecedencia INT(3)     NOT NULL DEFAULT 3          COMMENT 'Quantos dias antes do vencimento gerar alerta',
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuario_tipo (usuario_id, tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Configuração de alertas de notificação por usuário';

-- ------------------------------------------------------------
-- 3. Índices de performance
-- ------------------------------------------------------------
ALTER TABLE notificacoes
    ADD INDEX idx_notif_usuario_lida  (usuario_id, lida),
    ADD INDEX idx_notif_usuario_tipo  (usuario_id, tipo),
    ADD INDEX idx_notif_referencia    (referencia_tipo, referencia_id),
    ADD INDEX idx_notif_created       (created_at);

ALTER TABLE notificacao_config_alertas
    ADD INDEX idx_ncfg_usuario (usuario_id);

-- ------------------------------------------------------------
-- 4. SELECT de validação
-- ------------------------------------------------------------
SELECT
    (SELECT COUNT(*) FROM notificacoes)               AS total_notificacoes,
    (SELECT COUNT(*) FROM notificacao_config_alertas) AS total_configs;

-- ============================================================
-- ROLLBACK (executar apenas se precisar desfazer)
-- ============================================================
-- DROP TABLE IF EXISTS notificacao_config_alertas;
-- DROP TABLE IF EXISTS notificacoes;
