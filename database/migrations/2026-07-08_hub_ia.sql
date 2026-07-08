-- Migration: HUB I.A — Centro de Inteligência Artificial do ERP InLaudo
-- Date: 2026-07-08
-- Rules: ONLY CREATE TABLE / ADD COLUMN. Never drop/rename existing columns.
-- Compatibilidade: MySQL/MariaDB 5.7 (ver skill .claude/skills/mysql57-migrations).
-- Todas as tabelas são novas (CREATE TABLE IF NOT EXISTS, suportado nativamente
-- em MySQL 5.7 — não há ALTER TABLE ADD COLUMN nesta migration).
--
-- Observação de segurança: api_key_enc e token_enc armazenam o valor
-- criptografado via App\Services\CryptoService (AES-256-GCM), nunca texto puro
-- (mesma disciplina de integracoes.config_json / password_reset_tokens.token_hash).

-- ─── 1. Conectores de IA (provedores configurados) ──────────────────────────
CREATE TABLE IF NOT EXISTS hub_ia_conectores (
  id                    INT(11)       NOT NULL AUTO_INCREMENT,
  usuario_id            INT(11)       NOT NULL,
  nome                  VARCHAR(100)  NOT NULL COMMENT 'Nome de exibição, ex: OpenAI Produção',
  provider              ENUM('openai','claude','gemini','deepseek','mistral','ollama') NOT NULL,
  api_key_enc           TEXT          NULL COMMENT 'Chave de API criptografada (CryptoService)',
  endpoint              VARCHAR(255)  NULL COMMENT 'Base URL customizada (obrigatório para ollama/local)',
  modelo                VARCHAR(100)  NOT NULL DEFAULT '' COMMENT 'Ex: gpt-4o-mini, claude-3-5-sonnet-20241022',
  temperatura            DECIMAL(3,2)  NOT NULL DEFAULT 0.30,
  max_tokens            INT(11)       NOT NULL DEFAULT 2000,
  timeout_segundos      INT(11)       NOT NULL DEFAULT 30,
  status                ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo',
  ultimo_teste_em       DATETIME      NULL,
  ultimo_teste_status   ENUM('ok','erro') NULL,
  ultimo_teste_mensagem VARCHAR(500)  NULL,
  created_at            DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at            DATETIME      NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_hubia_conect_usuario  (usuario_id),
  INDEX idx_hubia_conect_provider (provider)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 2. Prompts reutilizáveis ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS hub_ia_prompts (
  id          INT(11)      NOT NULL AUTO_INCREMENT,
  usuario_id  INT(11)      NOT NULL,
  nome        VARCHAR(150) NOT NULL,
  categoria   VARCHAR(80)  NULL,
  conteudo    MEDIUMTEXT   NOT NULL,
  ativo       TINYINT(1)   NOT NULL DEFAULT 1,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_hubia_prompt_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 3. Agentes (Robôs IA) ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS hub_ia_agentes (
  id                      INT(11)      NOT NULL AUTO_INCREMENT,
  usuario_id              INT(11)      NOT NULL,
  nome                    VARCHAR(100) NOT NULL,
  avatar                  VARCHAR(10)  NULL DEFAULT '🤖',
  descricao               VARCHAR(500) NULL,
  conector_id             INT(11)      NULL,
  prompt_id               INT(11)      NULL,
  prompt_base             MEDIUMTEXT   NULL COMMENT 'Usado quando prompt_id é nulo (prompt inline)',
  temperatura             DECIMAL(3,2) NULL,
  idioma                  VARCHAR(20)  NOT NULL DEFAULT 'pt-BR',
  personalidade           VARCHAR(500) NULL,
  permite_consulta_banco  TINYINT(1)   NOT NULL DEFAULT 0,
  ativo                   TINYINT(1)   NOT NULL DEFAULT 1,
  created_at              DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at              DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_hubia_agente_usuario  (usuario_id),
  INDEX idx_hubia_agente_conector (conector_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 4. Permissões por módulo, por agente ────────────────────────────────────
CREATE TABLE IF NOT EXISTS hub_ia_agente_permissoes (
  id         INT(11) NOT NULL AUTO_INCREMENT,
  agente_id  INT(11) NOT NULL,
  modulo     ENUM('crm','financeiro','rdv','marketing','cnes','estoque','rh','configuracoes') NOT NULL,
  permitido  TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uk_hubia_agperm (agente_id, modulo),
  INDEX idx_hubia_agperm_agente (agente_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 5. Configuração do conector de banco (allowlist para NL→SQL) ───────────
-- Sempre opera sobre a própria conexão configurada em .env (Database::getInstance());
-- não permite apontar para host/usuário/senha arbitrários via tela (ver HubIaBancoController).
CREATE TABLE IF NOT EXISTS hub_ia_banco_config (
  id                  INT(11)    NOT NULL AUTO_INCREMENT,
  tabelas_liberadas   MEDIUMTEXT NULL COMMENT 'JSON array de nomes de tabelas/views liberadas para consulta via IA',
  ativo               TINYINT(1) NOT NULL DEFAULT 0,
  created_at          DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at          DATETIME   NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 6. Histórico de conversas (pergunta/resposta) ───────────────────────────
CREATE TABLE IF NOT EXISTS hub_ia_historico (
  id                     INT(11)      NOT NULL AUTO_INCREMENT,
  agente_id              INT(11)      NULL,
  usuario_id             INT(11)      NULL,
  modulo_origem          VARCHAR(50)  NOT NULL DEFAULT 'hub_ia',
  pergunta               MEDIUMTEXT   NOT NULL,
  resposta               MEDIUMTEXT   NULL,
  sql_gerado             MEDIUMTEXT   NULL,
  sql_linhas_retornadas  INT(11)      NULL,
  provider               VARCHAR(30)  NULL,
  modelo                 VARCHAR(100) NULL,
  tokens_prompt          INT(11)      NULL,
  tokens_resposta        INT(11)      NULL,
  tokens_total           INT(11)      NULL,
  custo_estimado_usd     DECIMAL(10,6) NULL,
  tempo_ms               INT(11)      NULL,
  ip_address             VARCHAR(45)  NULL,
  status                 ENUM('sucesso','erro') NOT NULL DEFAULT 'sucesso',
  created_at             DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_hubia_hist_agente   (agente_id),
  INDEX idx_hubia_hist_usuario  (usuario_id),
  INDEX idx_hubia_hist_created  (created_at),
  INDEX idx_hubia_hist_provider (provider)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 7. Logs técnicos por chamada de API ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS hub_ia_logs (
  id            INT(11)     NOT NULL AUTO_INCREMENT,
  conector_id   INT(11)     NULL,
  agente_id     INT(11)     NULL,
  historico_id  INT(11)     NULL,
  provider      VARCHAR(30) NULL,
  status_http   INT(11)     NULL,
  erro          TEXT        NULL,
  tempo_ms      INT(11)     NULL,
  tokens_total  INT(11)     NULL,
  created_at    DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_hubia_logs_conector (conector_id),
  INDEX idx_hubia_logs_created  (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 8. Configuração do WhatsApp (placeholder — integração futura) ──────────
CREATE TABLE IF NOT EXISTS hub_ia_whatsapp_config (
  id           INT(11)     NOT NULL AUTO_INCREMENT,
  numero       VARCHAR(30) NULL,
  token_enc    TEXT        NULL,
  webhook_url  VARCHAR(255) NULL,
  status       ENUM('desconectado','conectado') NOT NULL DEFAULT 'desconectado',
  created_at   DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME    NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 9. Base de Conhecimento — documentos ────────────────────────────────────
CREATE TABLE IF NOT EXISTS hub_ia_conhecimento_documentos (
  id             INT(11)      NOT NULL AUTO_INCREMENT,
  usuario_id     INT(11)      NOT NULL,
  nome_original  VARCHAR(255) NOT NULL,
  file_path      VARCHAR(500) NOT NULL,
  tipo           VARCHAR(10)  NOT NULL COMMENT 'pdf, docx, xlsx, txt',
  categoria      VARCHAR(80)  NULL,
  tamanho_bytes  INT(11)      NOT NULL DEFAULT 0,
  status         ENUM('processando','pronto','erro') NOT NULL DEFAULT 'processando',
  mensagem_erro  VARCHAR(500) NULL,
  total_chunks   INT(11)      NOT NULL DEFAULT 0,
  created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_hubia_doc_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 10. Base de Conhecimento — trechos (chunks) com embedding ──────────────
-- MySQL 5.7 não tem tipo vetorial nativo: embedding fica como JSON e a busca
-- por similaridade (coseno) é calculada em PHP (ver KnowledgeBaseService).
CREATE TABLE IF NOT EXISTS hub_ia_conhecimento_chunks (
  id            INT(11)    NOT NULL AUTO_INCREMENT,
  documento_id  INT(11)    NOT NULL,
  ordem         INT(11)    NOT NULL DEFAULT 0,
  conteudo      MEDIUMTEXT NOT NULL,
  embedding     LONGTEXT   NULL COMMENT 'JSON array de floats',
  created_at    DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_hubia_chunk_doc (documento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── VALIDAÇÃO ───────────────────────────────────────────────────────────────
SHOW TABLES LIKE 'hub_ia_%';
