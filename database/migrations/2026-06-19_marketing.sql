-- ============================================================
-- Módulo de Marketing: Campanhas e Gatilhos de Automação
-- Execute antes do deploy desta versão.
-- ============================================================

CREATE TABLE IF NOT EXISTS marketing_campanhas (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id       INT          NOT NULL,
    nome             VARCHAR(200) NOT NULL,
    tipo             ENUM('email','whatsapp','sms') NOT NULL DEFAULT 'email',
    status           ENUM('rascunho','agendada','enviando','concluida','cancelada') NOT NULL DEFAULT 'rascunho',
    assunto          VARCHAR(500)  DEFAULT NULL,
    conteudo         TEXT          DEFAULT NULL,
    data_agendamento DATETIME      DEFAULT NULL,
    total_destinatarios INT        NOT NULL DEFAULT 0,
    total_enviados      INT        NOT NULL DEFAULT 0,
    total_erros         INT        NOT NULL DEFAULT 0,
    created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_mktcamp_usuario (usuario_id),
    INDEX idx_mktcamp_status  (status),
    INDEX idx_mktcamp_tipo    (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS marketing_gatilhos (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id          INT          NOT NULL,
    nome                VARCHAR(200) NOT NULL,
    tipo                VARCHAR(50)  NOT NULL COMMENT 'aniversario|novo_cliente|pagamento_recebido|nf_emitida|contrato_vencendo|inatividade',
    canal               ENUM('email','whatsapp') NOT NULL DEFAULT 'email',
    ativo               TINYINT(1)   NOT NULL DEFAULT 1,
    assunto_email       VARCHAR(500)  DEFAULT NULL,
    conteudo_mensagem   TEXT          DEFAULT NULL,
    delay_dias          INT          NOT NULL DEFAULT 0,
    condicao_json       TEXT          DEFAULT NULL COMMENT 'JSON com condições extras',
    total_disparos      INT          NOT NULL DEFAULT 0,
    ultimo_disparo_em   DATETIME      DEFAULT NULL,
    created_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_mktgat_usuario (usuario_id),
    INDEX idx_mktgat_tipo    (tipo),
    INDEX idx_mktgat_ativo   (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
