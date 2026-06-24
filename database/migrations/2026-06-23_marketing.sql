-- ============================================================
-- Migração: Módulo de Marketing — Campanhas, Disparadores, Envios e Interações
-- Data: 2026-06-23 | Sistema: ERP InLaudo
-- Charset: utf8 / utf8_unicode_ci (compatível MySQL 5.7.44 Hostgator)
-- ============================================================
-- ⚠️ VERIFICAÇÕES ANTES DE EXECUTAR:
-- 1. Faça backup das tabelas existentes antes de executar.
-- 2. Execute em horário de baixo tráfego.
-- 3. Confirme que as tabelas NÃO existem: SHOW TABLES LIKE 'mkt_%';
-- ============================================================

-- ------------------------------------------------------------
-- 1. marketing_campanhas
--    Armazena as campanhas de marketing criadas pelo usuário.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS marketing_campanhas (
  id              INT(11)      NOT NULL AUTO_INCREMENT,
  usuario_id      INT(11)      NOT NULL                  COMMENT 'Tenant (users.id)',
  nome            VARCHAR(255) NOT NULL                  COMMENT 'Nome da campanha',
  descricao       TEXT         NULL                      COMMENT 'Descrição interna da campanha',
  canal           ENUM('email','whatsapp','telegram','sdr') NOT NULL DEFAULT 'email' COMMENT 'Canal de envio',
  status          ENUM('rascunho','ativa','pausada','arquivada') NOT NULL DEFAULT 'rascunho',
  -- Configuração de conteúdo (email)
  assunto_email   VARCHAR(255) NULL                      COMMENT 'Assunto do e-mail',
  tipo_conteudo   ENUM('texto','html') NOT NULL DEFAULT 'html' COMMENT 'Tipo de conteúdo do corpo',
  corpo           LONGTEXT     NULL                      COMMENT 'Corpo do e-mail (texto ou HTML) / mensagem WhatsApp/Telegram/SDR',
  -- Configuração de remetente
  remetente_nome  VARCHAR(255) NULL                      COMMENT 'Nome do remetente (email)',
  remetente_email VARCHAR(255) NULL                      COMMENT 'E-mail do remetente',
  -- Configuração WhatsApp/Telegram
  numero_origem   VARCHAR(30)  NULL                      COMMENT 'Número ou token de origem (WhatsApp/Telegram)',
  -- Metadados
  total_enviados  INT(11)      NOT NULL DEFAULT 0,
  total_abertos   INT(11)      NOT NULL DEFAULT 0,
  total_cliques   INT(11)      NOT NULL DEFAULT 0,
  total_erros     INT(11)      NOT NULL DEFAULT 0,
  created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_mkt_camp_usuario (usuario_id),
  INDEX idx_mkt_camp_canal   (canal),
  INDEX idx_mkt_camp_status  (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ------------------------------------------------------------
-- 2. marketing_disparadores
--    Configura um disparo: campanha + público + segmentação.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS marketing_disparadores (
  id              INT(11)      NOT NULL AUTO_INCREMENT,
  usuario_id      INT(11)      NOT NULL                  COMMENT 'Tenant (users.id)',
  campanha_id     INT(11)      NOT NULL                  COMMENT 'marketing_campanhas.id',
  nome            VARCHAR(255) NOT NULL                  COMMENT 'Nome/identificação do disparo',
  publico         ENUM('clientes','leads','oportunidades') NOT NULL DEFAULT 'leads' COMMENT 'Público-alvo',
  -- Segmentação (JSON com filtros aplicados)
  segmentacao     TEXT         NULL                      COMMENT 'JSON com filtros de segmentação aplicados',
  total_destinatarios INT(11)  NOT NULL DEFAULT 0        COMMENT 'Total de destinatários no momento do disparo',
  -- Controle de envio
  status          ENUM('rascunho','agendado','em_andamento','concluido','pausado','cancelado') NOT NULL DEFAULT 'rascunho',
  agendado_para   DATETIME     NULL                      COMMENT 'Data/hora agendada para disparo automático',
  iniciado_em     DATETIME     NULL,
  concluido_em    DATETIME     NULL,
  -- Configuração anti-blacklist (email)
  intervalo_envio INT(11)      NOT NULL DEFAULT 5        COMMENT 'Intervalo em segundos entre envios em lote',
  lote_tamanho    INT(11)      NOT NULL DEFAULT 5        COMMENT 'Quantidade de envios por lote',
  -- Progresso
  total_enviados  INT(11)      NOT NULL DEFAULT 0,
  total_erros     INT(11)      NOT NULL DEFAULT 0,
  log_execucao    TEXT         NULL,
  created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_mkt_disp_usuario   (usuario_id),
  INDEX idx_mkt_disp_campanha  (campanha_id),
  INDEX idx_mkt_disp_status    (status),
  INDEX idx_mkt_disp_agendado  (agendado_para)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ------------------------------------------------------------
-- 3. marketing_envios
--    Registro individual de cada envio realizado por um disparador.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS marketing_envios (
  id              INT(11)      NOT NULL AUTO_INCREMENT,
  disparador_id   INT(11)      NOT NULL                  COMMENT 'marketing_disparadores.id',
  usuario_id      INT(11)      NOT NULL                  COMMENT 'Tenant (users.id)',
  -- Destinatário
  destinatario_tipo  ENUM('cliente','lead','oportunidade') NOT NULL,
  destinatario_id    INT(11)   NOT NULL                  COMMENT 'ID do registro de origem',
  destinatario_nome  VARCHAR(255) NULL,
  destinatario_email VARCHAR(255) NULL,
  destinatario_tel   VARCHAR(30)  NULL,
  -- Resultado
  status          ENUM('pendente','enviado','erro','aberto','clicado','descadastrado') NOT NULL DEFAULT 'pendente',
  erro_msg        TEXT         NULL                      COMMENT 'Mensagem de erro se falhou',
  enviado_em      DATETIME     NULL,
  aberto_em       DATETIME     NULL,
  clicado_em      DATETIME     NULL,
  -- Rastreamento
  tracking_token  VARCHAR(64)  NULL                      COMMENT 'Token único para rastrear abertura/clique',
  created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_mkt_env_disparador    (disparador_id),
  INDEX idx_mkt_env_usuario       (usuario_id),
  INDEX idx_mkt_env_dest_tipo_id  (destinatario_tipo, destinatario_id),
  INDEX idx_mkt_env_status        (status),
  INDEX idx_mkt_env_token         (tracking_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ------------------------------------------------------------
-- 4. marketing_interacoes_crm
--    Vincula envios de marketing a registros do CRM para rastreamento
--    de evolução (lead/oportunidade → campanha → resultado).
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS marketing_interacoes_crm (
  id              INT(11)      NOT NULL AUTO_INCREMENT,
  usuario_id      INT(11)      NOT NULL,
  envio_id        INT(11)      NOT NULL                  COMMENT 'marketing_envios.id',
  campanha_id     INT(11)      NOT NULL                  COMMENT 'marketing_campanhas.id',
  -- Vínculo CRM
  related_type    ENUM('lead','oportunidade','cliente') NOT NULL,
  related_id      INT(11)      NOT NULL,
  -- Evento registrado
  evento          ENUM('enviado','aberto','clicado','respondido','convertido','descadastrado') NOT NULL DEFAULT 'enviado',
  observacao      TEXT         NULL,
  ocorrido_em     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_mkt_int_related  (related_type, related_id),
  INDEX idx_mkt_int_campanha (campanha_id),
  INDEX idx_mkt_int_envio    (envio_id),
  INDEX idx_mkt_int_usuario  (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ============================================================
-- VALIDAÇÃO
-- ============================================================
SELECT 'marketing_campanhas'      AS tabela, COUNT(*) AS total FROM marketing_campanhas
UNION ALL
SELECT 'marketing_disparadores'   AS tabela, COUNT(*) AS total FROM marketing_disparadores
UNION ALL
SELECT 'marketing_envios'         AS tabela, COUNT(*) AS total FROM marketing_envios
UNION ALL
SELECT 'marketing_interacoes_crm' AS tabela, COUNT(*) AS total FROM marketing_interacoes_crm;

-- ============================================================
-- ROLLBACK (executar em caso de necessidade de reversão)
-- ============================================================
-- DROP TABLE IF EXISTS marketing_interacoes_crm;
-- DROP TABLE IF EXISTS marketing_envios;
-- DROP TABLE IF EXISTS marketing_disparadores;
-- DROP TABLE IF EXISTS marketing_campanhas;
