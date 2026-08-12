-- ============================================================================
-- Baseline estrutural do ERP Imagiflow
-- Fonte: esquema de produção InLaudo extraído sem comandos de dados.
-- Uso: importar somente no banco NOVO e vazio inlaud99_saasimagiflow.
-- Não contém INSERT, REPLACE, LOAD DATA, anexos ou credenciais.
-- Não executar na base de produção original.
-- ============================================================================

-- DDL extraído de dump de produção; registros foram deliberadamente removidos.
-- Este arquivo é exclusivamente para auditoria e não deve ser executado sem revisão.

--

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
--

--
-- Procedimentos
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `apuracao_itens`
--

DROP TABLE IF EXISTS `apuracao_itens`;
CREATE TABLE `apuracao_itens` (
  `id` int(10) UNSIGNED NOT NULL,
  `apuracao_id` int(10) UNSIGNED NOT NULL,
  `linha_original` int(10) UNSIGNED DEFAULT NULL COMMENT 'Número da linha no arquivo importado',
  `unidade` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `medico_nome` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `medico_crm` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `revisor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_revisao` datetime DEFAULT NULL,
  `modalidade` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `study_description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paciente_nome` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paciente_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prioridade` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Normal ou Urgencia',
  `origem` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registro` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_estudo` datetime DEFAULT NULL,
  `data_conclusao` datetime DEFAULT NULL,
  `sla` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `accession_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `visita` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `convenio` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `valor_importado` decimal(12,2) DEFAULT '0.00',
  `valor_exame_import` decimal(12,2) DEFAULT '0.00',
  `exame_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'FK tabela_exames',
  `valor_calculado` decimal(12,2) DEFAULT '0.00',
  `valor_calculado_venda` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Valor de venda calculado por item (preço de venda da tabela de exames)',
  `tipo_prioridade` enum('normal','urgencia') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `status_item` enum('ok','sem_match','erro') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ok',
  `obs_item` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `apuracao_itens`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `apuracoes`
--

DROP TABLE IF EXISTS `apuracoes`;
CREATE TABLE `apuracoes` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `contrato_id` int(10) UNSIGNED NOT NULL,
  `apuracao_mae_id` int(10) UNSIGNED DEFAULT NULL,
  `numero` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Número aleatório gerado',
  `tipo` enum('prestador','cliente') COLLATE utf8mb4_unicode_ci NOT NULL,
  `medico_id` int(10) UNSIGNED DEFAULT NULL,
  `cliente_id` int(10) UNSIGNED DEFAULT NULL,
  `periodo_inicio` date DEFAULT NULL,
  `periodo_fim` date DEFAULT NULL,
  `total_exames` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `total_normal` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `total_urgencia` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `valor_total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `valor_venda_total` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Valor total de venda (preço cobrado do cliente)',
  `status` enum('rascunho','processando','concluido','faturado','erro') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'rascunho',
  `origem` enum('manual','automatico','pacs') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `arquivo_import` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Caminho do arquivo importado',
  `log_execucao` text COLLATE utf8mb4_unicode_ci COMMENT 'Log do processo de apuração',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `apuracoes`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `context` json DEFAULT NULL,
  `details` longtext COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `audit_logs`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `clientes`
--

DROP TABLE IF EXISTS `clientes`;
CREATE TABLE `clientes` (
  `id` int(11) NOT NULL COMMENT 'ID único do cliente',
  `tipo` enum('PF','PJ') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PJ' COMMENT 'Tipo de cliente: Pessoa Física ou Jurídica',
  `cpf_cnpj` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'CPF (PF) ou CNPJ (PJ) - sem formatação',
  `razao_social` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Razão Social (PJ) ou Nome Completo (PF)',
  `nome_fantasia` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nome Fantasia (PJ) ou Apelido (PF)',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'E-mail principal do cliente',
  `website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Website/URL do cliente',
  `cnae_principal` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'CNAE Principal (PJ)',
  `descricao_cnae` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Descrição do CNAE',
  `endereco` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Rua/Avenida',
  `numero` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Número',
  `complemento` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Complemento (apto, sala, etc)',
  `bairro` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Bairro',
  `cidade` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Cidade',
  `estado` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Estado (UF)',
  `cep` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'CEP',
  `telefone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Telefone comercial',
  `celular` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Celular principal',
  `instagram` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Usuário Instagram',
  `tiktok` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Usuário TikTok',
  `facebook` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Usuário/Página Facebook',
  `status` enum('ativo','inativo','suspenso') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ativo' COMMENT 'Status do cliente',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_cadastro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de cadastro',
  `data_atualizacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Data da última atualização',
  `usuario_id` int(11) NOT NULL COMMENT 'ID do usuário que cadastrou',
  `linkedin` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Perfil LinkedIn',
  `segmento_principal` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Segmento: clinica_imagem, hospital, etc.',
  `especialidades_interesse` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON array de especialidades/modalidades',
  `volume_exames_mes` int(11) DEFAULT NULL COMMENT 'Estimativa de exames/mês',
  `equipamentos_possui` text COLLATE utf8mb4_unicode_ci COMMENT 'Equipamentos que a clínica possui',
  `sistema_atual` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Sistema/software atual',
  `num_medicos` int(11) DEFAULT NULL COMMENT 'Número de médicos/radiologistas',
  `num_unidades` int(11) DEFAULT NULL COMMENT 'Número de unidades',
  `acreditacao` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Acreditações (ONA, JCI, etc.)',
  `responsavel_nome` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `responsavel_cargo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ex: Diretor Clínico, Gestor de TI',
  `responsavel_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `responsavel_telefone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `crm_lead_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'ID do lead CRM'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabela de Clientes - Armazena informações de PF e PJ';

--
-- Despejando dados para a tabela `clientes`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `clientes_anexos`
--

DROP TABLE IF EXISTS `clientes_anexos`;
CREATE TABLE `clientes_anexos` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `clientes_anexos`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `clientes_contatos`
--

DROP TABLE IF EXISTS `clientes_contatos`;
CREATE TABLE `clientes_contatos` (
  `id` int(11) NOT NULL COMMENT 'ID único do contato',
  `cliente_id` int(11) NOT NULL COMMENT 'ID do cliente (FK)',
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nome do contato',
  `departamento` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Departamento (ex: Financeiro, RH, etc)',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'E-mail do contato',
  `celular` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Celular do contato',
  `telefone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Telefone do contato',
  `cargo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Cargo/Função',
  `observacoes` text COLLATE utf8mb4_unicode_ci COMMENT 'Observações adicionais',
  `status` enum('ativo','inativo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ativo' COMMENT 'Status do contato',
  `data_cadastro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de cadastro',
  `data_atualizacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Data da última atualização'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabela de Contatos - Relacionamento 1:N com Clientes';

--
-- Despejando dados para a tabela `clientes_contatos`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `cnes_dom_cbo`
--

DROP TABLE IF EXISTS `cnes_dom_cbo`;
CREATE TABLE `cnes_dom_cbo` (
  `co_cbo` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `no_cbo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Domínio CBO — Classificação Brasileira de Ocupações (saúde)';

--
-- Despejando dados para a tabela `cnes_dom_cbo`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `cnes_dom_conselho`
--

DROP TABLE IF EXISTS `cnes_dom_conselho`;
CREATE TABLE `cnes_dom_conselho` (
  `co_conselho` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `no_conselho` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Domínio de Conselhos de Classe CNES';

--
-- Despejando dados para a tabela `cnes_dom_conselho`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `cnes_dom_equipamentos`
--

DROP TABLE IF EXISTS `cnes_dom_equipamentos`;
CREATE TABLE `cnes_dom_equipamentos` (
  `co_equipamento` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `no_equipamento` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `co_tipo` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Domínio de equipamentos CNES';

--
-- Despejando dados para a tabela `cnes_dom_equipamentos`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `cnes_dom_tipo_equipamento`
--

DROP TABLE IF EXISTS `cnes_dom_tipo_equipamento`;
CREATE TABLE `cnes_dom_tipo_equipamento` (
  `co_tipo` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `no_tipo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Domínio de tipos de equipamento CNES';

--
-- Despejando dados para a tabela `cnes_dom_tipo_equipamento`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `cnes_equipamentos`
--

DROP TABLE IF EXISTS `cnes_equipamentos`;
CREATE TABLE `cnes_equipamentos` (
  `id` int(11) NOT NULL,
  `co_unidade` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'FK para cnes_estabelecimentos.co_unidade',
  `co_equipamento` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Código do equipamento CNES',
  `no_equipamento` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Descrição do equipamento (preenchida por lookup)',
  `co_tipo_equipamento` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Código tipo equipamento (1=Diagnóstico por Imagem, etc.)',
  `no_tipo_equipamento` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Descrição do tipo',
  `qt_existente` int(11) DEFAULT '0' COMMENT 'Quantidade existente',
  `qt_uso` int(11) DEFAULT '0' COMMENT 'Quantidade em uso',
  `tp_sus` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Atende SUS (1=Sim, 2=Não)',
  `qt_sus` int(11) DEFAULT '0' COMMENT 'Quantidade disponível para SUS',
  `dt_atualizacao` date DEFAULT NULL,
  `fabricante` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Fabricante do equipamento',
  `modelo` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Modelo do equipamento',
  `ano_instalacao` year(4) DEFAULT NULL COMMENT 'Ano de instalação',
  `observacoes` text COLLATE utf8mb4_unicode_ci COMMENT 'Observações adicionais',
  `atualizado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `co_cnes` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `competencia` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Equipamentos por estabelecimento CNES';

--
-- Despejando dados para a tabela `cnes_equipamentos`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `cnes_estabelecimentos`
--

DROP TABLE IF EXISTS `cnes_estabelecimentos`;
CREATE TABLE `cnes_estabelecimentos` (
  `id` int(11) NOT NULL,
  `co_unidade` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Código único da unidade (CO_UNIDADE)',
  `co_cnes` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Número CNES do estabelecimento',
  `nu_cnpj` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'CNPJ do estabelecimento',
  `nu_cnpj_mantenedora` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'CNPJ da mantenedora',
  `tp_pfpj` tinyint(1) DEFAULT NULL COMMENT '1=PF, 3=PJ',
  `no_razao_social` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Razão Social',
  `no_fantasia` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nome Fantasia',
  `no_fantasia_abrev` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nome Fantasia Abreviado',
  `tp_unidade` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Tipo de Unidade (código)',
  `co_tipo_unidade` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `co_tipo_estabelecimento` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `no_logradouro` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Logradouro',
  `nu_endereco` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Número do endereço',
  `no_complemento` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Complemento',
  `no_bairro` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Bairro',
  `co_cep` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'CEP',
  `co_municipio_gestor` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Código IBGE do município',
  `co_estado_gestor` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'UF',
  `nu_telefone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Telefone',
  `nu_fax` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Fax',
  `no_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'E-mail',
  `no_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Website',
  `nu_latitude` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Latitude',
  `nu_longitude` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Longitude',
  `co_natureza_jur` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Código Natureza Jurídica',
  `co_natureza_juridica` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tp_gestao` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Tipo de Gestão (M=Municipal, E=Estadual, D=Dupla)',
  `co_atividade` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Código Atividade',
  `co_clientela` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Código Clientela',
  `co_turno_atendimento` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Turno de Atendimento',
  `st_conexao_internet` varchar(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Conexão Internet (S/N)',
  `tp_estab_sempre_aberto` varchar(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Sempre Aberto (S/N)',
  `nu_cpf_diretor` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'CPF do Diretor Clínico',
  `co_cpf_diretor_clinico` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reg_diretor` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Registro do Diretor',
  `reg_diretor_clinico` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `co_motivo_desabilitacao` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dt_atualizacao` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Data de atualização no CNES',
  `competencia` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cnes_importado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de importação para o ERP',
  `cliente_id` int(11) DEFAULT NULL COMMENT 'FK para clientes — preenchido ao importar como cliente',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Estabelecimentos CNES — importado da base pública DATASUS';

--
-- Despejando dados para a tabela `cnes_estabelecimentos`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `cnes_importacoes`
--

DROP TABLE IF EXISTS `cnes_importacoes`;
CREATE TABLE `cnes_importacoes` (
  `id` int(11) NOT NULL,
  `competencia` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'AAAAMM',
  `status` enum('processando','concluido','erro') COLLATE utf8mb4_unicode_ci DEFAULT 'processando',
  `total_estab` int(11) DEFAULT '0',
  `total_equip` int(11) DEFAULT '0',
  `total_prof` int(11) DEFAULT '0',
  `log` text COLLATE utf8mb4_unicode_ci,
  `usuario_id` int(11) DEFAULT NULL,
  `iniciado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `concluido_em` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Histórico de importações da base CNES';

--
-- Despejando dados para a tabela `cnes_importacoes`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `cnes_profissionais`
--

DROP TABLE IF EXISTS `cnes_profissionais`;
CREATE TABLE `cnes_profissionais` (
  `id` int(11) NOT NULL,
  `co_unidade` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'FK para cnes_estabelecimentos.co_unidade',
  `co_profissional_sus` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Código interno CNES do profissional',
  `no_profissional` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nome do profissional',
  `co_cbo` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Código CBO da ocupação',
  `no_cbo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Descrição CBO',
  `co_conselho_classe` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Código conselho (CRM, CRN, etc.)',
  `no_conselho_classe` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Sigla do conselho',
  `nu_registro` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Número de registro no conselho',
  `nu_registro_conselho` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sg_uf_crm` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'UF do registro',
  `sg_uf_conselho` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tp_sus_nao_sus` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Atende SUS (S/N)',
  `ind_vinculacao` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Indicador de vínculo',
  `qt_carga_horaria_amb` int(11) DEFAULT '0' COMMENT 'Carga horária ambulatorial',
  `qt_carga_horaria_outros` int(11) DEFAULT '0' COMMENT 'Carga horária outros',
  `situacao` enum('ativo','inativo') COLLATE utf8mb4_unicode_ci DEFAULT 'ativo' COMMENT 'Situação do profissional',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'E-mail do profissional (inserido pelo usuário)',
  `contato` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Telefone/WhatsApp do profissional',
  `observacoes` text COLLATE utf8mb4_unicode_ci COMMENT 'Observações adicionais',
  `atualizado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `co_cnes` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `competencia` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dt_atualizacao` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Profissionais de saúde por estabelecimento CNES';

--
-- Despejando dados para a tabela `cnes_profissionais`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `colaboradores`
--

DROP TABLE IF EXISTS `colaboradores`;
CREATE TABLE `colaboradores` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL COMMENT 'Tenant: ID do usuário dono do cadastro',
  `tipo_contratacao` enum('CLT','PJ') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CLT' COMMENT 'CLT = Pessoa Física; PJ = Pessoa Jurídica',
  `cpf_cnpj` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'CPF (CLT) ou CNPJ (PJ) sem formatação',
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nome completo (CLT) ou Razão Social (PJ)',
  `nome_social` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nome social / Nome Fantasia',
  `data_nascimento` date DEFAULT NULL,
  `rg` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `orgao_emissor` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pis_pasep` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ctps` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Carteira de Trabalho',
  `ctps_serie` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado_civil` enum('solteiro','casado','divorciado','viuvo','uniao_estavel','outro') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `escolaridade` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inscricao_estadual` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inscricao_municipal` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cnae_principal` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descricao_cnae` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nome_responsavel` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nome do responsável legal (PJ)',
  `cpf_responsavel` varchar(14) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `celular` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cep` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endereco` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `complemento` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bairro` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cidade` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cargo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `departamento` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_admissao` date DEFAULT NULL,
  `data_demissao` date DEFAULT NULL,
  `salario_base` decimal(15,2) DEFAULT NULL,
  `banco` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agencia` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `conta` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_conta` enum('corrente','poupanca','salario') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `chave_pix` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'FK para users.id — usuário do sistema vinculado',
  `status` enum('ativo','inativo','afastado','demitido') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ativo',
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabela de Colaboradores (CLT e PJ)';

--
-- Despejando dados para a tabela `colaboradores`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `colaboradores_anexos`
--

DROP TABLE IF EXISTS `colaboradores_anexos`;
CREATE TABLE `colaboradores_anexos` (
  `id` int(11) NOT NULL,
  `colaborador_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nome_anexo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nome/descrição do documento',
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` int(11) NOT NULL DEFAULT '0',
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'application/octet-stream',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Anexos e documentos dos colaboradores';

--
-- Despejando dados para a tabela `colaboradores_anexos`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `colaboradores_comissoes`
--

DROP TABLE IF EXISTS `colaboradores_comissoes`;
CREATE TABLE `colaboradores_comissoes` (
  `id` int(11) NOT NULL,
  `colaborador_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Descrição da regra de comissão',
  `tipo` enum('percentual','valor_fixo','por_exame','por_contrato') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'percentual',
  `valor` decimal(10,4) NOT NULL DEFAULT '0.0000' COMMENT 'Percentual (ex: 5.5000 = 5,5%) ou valor fixo',
  `base_calculo` enum('faturamento_bruto','faturamento_liquido','valor_exame','valor_contrato') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'faturamento_bruto',
  `vigencia_inicio` date DEFAULT NULL,
  `vigencia_fim` date DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Regras de comissão dos colaboradores';

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracoes_financeiras`
--

DROP TABLE IF EXISTS `configuracoes_financeiras`;
CREATE TABLE `configuracoes_financeiras` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `meio_pagamento_padrao` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'checkout',
  `juros_tipo` enum('PERCENTAGE','FIXED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PERCENTAGE',
  `juros_valor` decimal(8,4) NOT NULL DEFAULT '1.0000',
  `juros_dias_carencia` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `multa_tipo` enum('PERCENTAGE','FIXED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PERCENTAGE',
  `multa_valor` decimal(8,4) NOT NULL DEFAULT '2.0000',
  `multa_dias_carencia` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `desconto_ativo` tinyint(1) NOT NULL DEFAULT '0',
  `desconto_tipo` enum('PERCENTAGE','FIXED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PERCENTAGE',
  `desconto_valor` decimal(8,4) NOT NULL DEFAULT '0.0000',
  `desconto_dias_antes` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `desconto_limite_data` date DEFAULT NULL,
  `boleto_dias_vencimento` tinyint(3) UNSIGNED NOT NULL DEFAULT '3',
  `boleto_instrucoes` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `boleto_aceite` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'N',
  `boleto_banco` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `pix_expiracao_segundos` int(10) UNSIGNED NOT NULL DEFAULT '86400',
  `pix_chave` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cartao_max_parcelas` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `cartao_parcela_minima` decimal(10,2) NOT NULL DEFAULT '50.00',
  `cartao_juros_parcelamento` decimal(6,4) NOT NULL DEFAULT '0.0000',
  `checkout_meios_habilitados` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'BOLETO,PIX,CREDIT_CARD',
  `notificar_email` tinyint(1) NOT NULL DEFAULT '1',
  `notificar_sms` tinyint(1) NOT NULL DEFAULT '0',
  `notificar_whatsapp` tinyint(1) NOT NULL DEFAULT '0',
  `dias_aviso_vencimento` tinyint(3) UNSIGNED NOT NULL DEFAULT '3',
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `configuracoes_financeiras`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `config_nfs`
--

DROP TABLE IF EXISTS `config_nfs`;
CREATE TABLE `config_nfs` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL COMMENT 'Tenant/usuário dono da config',
  `layout_tipo` enum('padrao','personalizado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'padrao' COMMENT 'padrao = envia apenas valor+data; personalizado = envia JSON completo',
  `service_description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SERVIÇOS DE LAUDO' COMMENT 'Descrição padrão do serviço para todas as NFs',
  `observations` text COLLATE utf8mb4_unicode_ci COMMENT 'Observações padrão impressas na NF',
  `municipal_service_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Serviços de Saúde / Radiologia' COMMENT 'Nome do serviço municipal configurado no Asaas',
  `municipal_service_code` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Código de serviço municipal (ex: 4.03)',
  `municipal_service_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ID único do serviço municipal no Asaas (alternativo ao code)',
  `cnae` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '8640205' COMMENT 'CNAE da empresa (ex: 8640205 para radiologia)',
  `nbs_codigo` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Código NBS (ex: 1.07.00.00.00) exigido pelo Portal Nacional de NFS-e',
  `deductions` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Deduções padrão (não alteram o valor, apenas a base de cálculo do ISS)',
  `retain_iss` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Reter ISS na fonte',
  `iss_aliquota` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'Alíquota ISS (%)',
  `pis_aliquota` decimal(5,2) NOT NULL DEFAULT '0.00',
  `cofins_aliquota` decimal(5,2) NOT NULL DEFAULT '0.00',
  `csll_aliquota` decimal(5,2) NOT NULL DEFAULT '0.00',
  `inss_aliquota` decimal(5,2) NOT NULL DEFAULT '0.00',
  `ir_aliquota` decimal(5,2) NOT NULL DEFAULT '0.00',
  `json_template` text COLLATE utf8mb4_unicode_ci COMMENT 'Template JSON personalizado. Placeholders: {{value}}, {{effectiveDate}}, {{payment}}, {{descricao}}',
  `emite_portal_nacional` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1 = emite pelo Portal Nacional (NFS-e Nacional)',
  `serie_nf` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Série da NF (80000-89999 para Portal Nacional)',
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Configurações de emissão de NFS-e via Asaas (Portal Nacional)';

--
-- Despejando dados para a tabela `config_nfs`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `contas_bancarias`
--

DROP TABLE IF EXISTS `contas_bancarias`;
CREATE TABLE `contas_bancarias` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nome/apelido da conta (ex: Conta Principal Itaú)',
  `banco_codigo` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Código COMPE do banco (ex: 341)',
  `banco_nome` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nome do banco (ex: Itaú Unibanco)',
  `banco_ispb` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ISPB do banco para Open Finance',
  `tipo` enum('corrente','poupanca','investimento','caixa','outro') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'corrente',
  `agencia` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agencia_digito` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `conta` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `conta_digito` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `titular` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nome do titular da conta',
  `cpf_cnpj` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'CPF/CNPJ do titular',
  `saldo_inicial` decimal(15,2) NOT NULL DEFAULT '0.00',
  `saldo_atual` decimal(15,2) NOT NULL DEFAULT '0.00',
  `moeda` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'BRL',
  `cor` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '#4361ee' COMMENT 'Cor de identificação visual',
  `icone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'fas fa-university',
  `ativa` tinyint(1) NOT NULL DEFAULT '1',
  `openfinance_item_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Item ID no Pluggy/Open Finance',
  `openfinance_account_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Account ID no Pluggy/Open Finance',
  `openfinance_provider` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Provider: pluggy, belvo, etc.',
  `openfinance_last_sync` datetime DEFAULT NULL COMMENT 'Última sincronização Open Finance',
  `openfinance_status` enum('connected','disconnected','error','pending') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `openfinance_config` json DEFAULT NULL COMMENT 'Configurações extras do Open Finance',
  `openfinance_connector` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nome do conector/instituiu00e7u00e3o no Pluggy',
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `contas_bancarias`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `contas_movimentacoes`
--

DROP TABLE IF EXISTS `contas_movimentacoes`;
CREATE TABLE `contas_movimentacoes` (
  `id` int(10) UNSIGNED NOT NULL,
  `conta_bancaria_id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `data_movimentacao` date NOT NULL,
  `data_compensacao` date DEFAULT NULL,
  `descricao` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao_original` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Descrição original do banco',
  `valor` decimal(15,2) NOT NULL COMMENT 'Positivo=crédito, Negativo=débito',
  `tipo` enum('credito','debito') COLLATE utf8mb4_unicode_ci NOT NULL,
  `saldo_apos` decimal(15,2) DEFAULT NULL COMMENT 'Saldo após a movimentação',
  `categoria` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `plano_conta_id` int(10) UNSIGNED DEFAULT NULL,
  `tags` json DEFAULT NULL,
  `origem` enum('manual','ofx','ofc','openfinance','apuracao','conta_pagar','conta_receber','importacao') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `origem_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ID externo (ex: ID da transação no banco)',
  `origem_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Hash para deduplicação de importações',
  `conta_pagar_id` int(10) UNSIGNED DEFAULT NULL,
  `conta_receber_id` int(10) UNSIGNED DEFAULT NULL,
  `openfinance_tx_id` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Transaction ID do Open Finance',
  `openfinance_data` json DEFAULT NULL COMMENT 'Dados brutos da transação Open Finance',
  `conciliada` tinyint(1) NOT NULL DEFAULT '0',
  `data_conciliacao` datetime DEFAULT NULL,
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `contas_pagar`
--

DROP TABLE IF EXISTS `contas_pagar`;
CREATE TABLE `contas_pagar` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `plano_conta_id` int(11) DEFAULT NULL,
  `fornecedor_id` int(11) DEFAULT NULL,
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` decimal(15,2) NOT NULL,
  `data_vencimento` date NOT NULL,
  `data_pagamento` date DEFAULT NULL,
  `codigo_barras` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recorrente` tinyint(1) NOT NULL DEFAULT '0',
  `recorrencia_tipo` enum('mensal','semanal','anual','customizada') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recorrencia_intervalo` int(11) DEFAULT NULL,
  `status` enum('aberta','paga','cancelada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aberta',
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `contas_pagar`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `contas_pagar_anexos`
--

DROP TABLE IF EXISTS `contas_pagar_anexos`;
CREATE TABLE `contas_pagar_anexos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `conta_pagar_id` int(11) NOT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `contas_pagar_anexos`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `contas_receber`
--

DROP TABLE IF EXISTS `contas_receber`;
CREATE TABLE `contas_receber` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `plano_conta_id` int(11) DEFAULT NULL,
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` decimal(15,2) NOT NULL,
  `data_vencimento` date NOT NULL,
  `data_recebimento` date DEFAULT NULL,
  `status` enum('aberta','recebida','cancelada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aberta',
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `emitir_nf_avulsa` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = emitir NFS-e avulsa ao salvar/receber esta conta',
  `nf_avulsa_status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Status da NF avulsa: pendente | agendada | emitida | erro',
  `nf_avulsa_nota_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ID da nota fiscal no Asaas (ex: nfi_xxx)',
  `meio_pagamento` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recorrente` tinyint(1) NOT NULL DEFAULT '0',
  `recorrencia_tipo` enum('mensal','semanal','anual','customizada') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recorrencia_intervalo` int(11) DEFAULT NULL,
  `asaas_payment_id` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `asaas_subscription_id` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cora_invoice_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ID da fatura gerada na Cora',
  `cora_boleto_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'URL do boleto gerado pela Cora',
  `cora_boleto_pdf` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'URL do PDF do boleto Cora',
  `cora_pix_qrcode` text COLLATE utf8mb4_unicode_ci COMMENT 'QR Code Pix gerado pela Cora',
  `numero_parcela` smallint(5) UNSIGNED DEFAULT NULL COMMENT 'Número da parcela atual',
  `total_parcelas` smallint(5) UNSIGNED DEFAULT NULL COMMENT 'Total de parcelas do grupo',
  `grupo_parcelas` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Identificador do grupo de parcelas',
  `contrato_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Contrato que originou esta conta a receber',
  `recorrencia_modo` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'rolling' COMMENT 'rolling = gera próxima ao receber; antecipado = gerou todas de uma vez',
  `juros_percentual` decimal(5,2) DEFAULT NULL COMMENT 'Snapshot do percentual de juros aplicado',
  `multa_percentual` decimal(5,2) DEFAULT NULL COMMENT 'Snapshot do percentual de multa aplicado',
  `external_reference` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `colaborador_id` int(11) DEFAULT NULL COMMENT 'FK para colaboradores.id — colaborador vinculado à conta'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `contas_receber`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `contas_receber_anexos`
--

DROP TABLE IF EXISTS `contas_receber_anexos`;
CREATE TABLE `contas_receber_anexos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `conta_receber_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Despejando dados para a tabela `contas_receber_anexos`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `contratos`
--

DROP TABLE IF EXISTS `contratos`;
CREATE TABLE `contratos` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `numero` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Número único do contrato',
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_parte` enum('medico','cliente') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'medico=pagamento, cliente=recebimento',
  `medico_id` int(10) UNSIGNED DEFAULT NULL,
  `cliente_id` int(10) UNSIGNED DEFAULT NULL,
  `data_inicio` date NOT NULL,
  `data_fim` date DEFAULT NULL,
  `vigencia_tipo` enum('determinado','indeterminado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'determinado',
  `recorrencia` enum('diario','semanal','mensal','anual') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'mensal',
  `valor` decimal(12,2) NOT NULL DEFAULT '0.00',
  `dia_vencimento` tinyint(3) UNSIGNED DEFAULT NULL COMMENT 'Dia do mês para vencimento das parcelas (1-28)',
  `num_parcelas` smallint(5) UNSIGNED DEFAULT NULL COMMENT 'Número de parcelas a gerar automaticamente',
  `plano_conta_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Plano de conta para as cobranças geradas automaticamente',
  `meio_pagamento` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Meio de pagamento padrão para cobranças do contrato',
  `cobrancas_geradas` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = cobranças já foram geradas para este contrato',
  `cobrancas_geradas_em` datetime DEFAULT NULL COMMENT 'Quando as cobranças foram geradas pela última vez',
  `dias_vencimento_apuracao` smallint(5) UNSIGNED DEFAULT '5' COMMENT 'Dias após a confirmação da apuração até o vencimento do boleto',
  `juros_percentual` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'Percentual de juros ao mês cobrado em atraso',
  `multa_percentual` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'Percentual de multa por atraso',
  `boleto_automatico` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = gerar boleto/cobrança automaticamente no Asaas ao confirmar apuração',
  `portal_habilitado` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1 = liberar boleto/NF gerados para o cliente no portal',
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `status` enum('ativo','encerrado','suspenso') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ativo',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `contratos`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `contratos_anexos`
--

DROP TABLE IF EXISTS `contratos_anexos`;
CREATE TABLE `contratos_anexos` (
  `id` int(10) UNSIGNED NOT NULL,
  `contrato_id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` int(10) UNSIGNED DEFAULT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `contrato_exames`
--

DROP TABLE IF EXISTS `contrato_exames`;
CREATE TABLE `contrato_exames` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `contrato_id` int(10) UNSIGNED NOT NULL,
  `tabela_exame_id` int(10) UNSIGNED NOT NULL,
  `valor_rotina` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Valor de rotina para o médico (override do contrato)',
  `valor_urgencia` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Valor de urgência para o médico (override do contrato)',
  `valor_venda_rotina` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Valor de venda rotina para o cliente (override do contrato)',
  `valor_venda_urgencia` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Valor de venda urgência para o cliente (override do contrato)',
  `usa_valor_custom` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = usa valores do contrato como base contábil; 0 = usa tabela de exames',
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Exames vinculados a contratos com possibilidade de override de valores';

--
-- Despejando dados para a tabela `contrato_exames`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `contrato_modalidades`
--

DROP TABLE IF EXISTS `contrato_modalidades`;
CREATE TABLE `contrato_modalidades` (
  `id` int(10) UNSIGNED NOT NULL,
  `contrato_id` int(10) UNSIGNED NOT NULL,
  `modalidade` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'TC, RM, RX, US, DX, CR, etc.',
  `exame_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'FK tabela_exames (opcional)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `crm_anexos`
--

DROP TABLE IF EXISTS `crm_anexos`;
CREATE TABLE `crm_anexos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `related_type` enum('lead','oportunidade') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tipo do registro vinculado',
  `related_id` int(11) NOT NULL COMMENT 'ID do lead ou oportunidade',
  `nome_documento` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nome descritivo do documento',
  `tipo_documento` enum('contrato','termo_aceite','proposta_comercial','edital','outro') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'outro' COMMENT 'Tipo do documento',
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Caminho relativo do arquivo no servidor',
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nome original do arquivo enviado',
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL COMMENT 'Tamanho em bytes',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Anexos de documentos vinculados a Leads e Oportunidades do CRM';

-- --------------------------------------------------------

--
-- Estrutura para tabela `crm_interacoes`
--

DROP TABLE IF EXISTS `crm_interacoes`;
CREATE TABLE `crm_interacoes` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL COMMENT 'Quem registrou a interação (users.id)',
  `related_id` int(11) NOT NULL COMMENT 'ID do lead ou oportunidade',
  `related_type` enum('lead','oportunidade') COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_interacao` datetime NOT NULL COMMENT 'Data e hora da interação',
  `tipo_interacao` enum('email','telefone','whatsapp','reuniao_presencial','reuniao_online','visita_tecnica','proposta_enviada','contrato_enviado','transferencia','outro') COLLATE utf8mb4_unicode_ci NOT NULL,
  `resumo` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'O que foi discutido / resultado',
  `data_retorno` date DEFAULT NULL COMMENT 'Data programada para o próximo retorno após esta interação',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `crm_interacoes`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `crm_leads`
--

DROP TABLE IF EXISTS `crm_leads`;
CREATE TABLE `crm_leads` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL COMMENT 'Tenant (users.id)',
  `nome_lead` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nome da empresa ou pessoa',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `celular` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `instagram` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `linkedin` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cnpj` varchar(18) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'CNPJ para busca automática',
  `cpf` varchar(14) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_pessoa` enum('PJ','PF') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PJ',
  `razao_social` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nome_fantasia` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cnae_principal` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descricao_cnae` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endereco` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `complemento` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bairro` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cidade` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` char(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cep` varchar(9) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `origem` enum('indicacao','site','evento','linkedin','prospeccao_ativa','parceiro','outro') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'outro' COMMENT 'Como o lead chegou',
  `status_lead` enum('novo','contatado','qualificado','descartado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'novo',
  `segmento_principal` enum('clinica_imagem','hospital','upa_pronto_socorro','laboratorio','clinica_ortopedica','clinica_oncologica','consultorio_medico','outro') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Tipo de estabelecimento',
  `especialidades_interesse` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON array: ["Tomografia","Ressonância","Raio-X","Mamografia","Ultrassom","Densitometria","PET-CT","Outro"]',
  `volume_exames_mes` int(11) DEFAULT NULL COMMENT 'Estimativa de exames/mês',
  `equipamentos_possui` text COLLATE utf8mb4_unicode_ci COMMENT 'Equipamentos que o lead já possui',
  `sistema_atual` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Sistema/software que utiliza atualmente',
  `num_medicos` int(11) DEFAULT NULL COMMENT 'Quantidade de médicos/radiologistas',
  `num_unidades` int(11) DEFAULT NULL COMMENT 'Quantidade de unidades/filiais',
  `acreditacao` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Acreditações (ONA, JCI, etc.)',
  `responsavel_nome` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `responsavel_cargo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ex: Diretor Clínico, Gestor de TI, Sócio',
  `responsavel_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `responsavel_telefone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `produtos_interesse` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON array com IDs ou nomes de produtos/serviços',
  `data_proximo_contato` date DEFAULT NULL COMMENT 'Agendamento de follow-up',
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `convertido_em` enum('oportunidade','cliente') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Indica se foi convertido',
  `convertido_id` int(11) DEFAULT NULL COMMENT 'ID da oportunidade ou cliente gerado',
  `convertido_em_data` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `crm_leads`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `crm_oportunidades`
--

DROP TABLE IF EXISTS `crm_oportunidades`;
CREATE TABLE `crm_oportunidades` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL COMMENT 'Tenant (users.id)',
  `lead_id` int(11) DEFAULT NULL COMMENT 'Lead de origem (crm_leads.id)',
  `cliente_id` int(11) DEFAULT NULL COMMENT 'Cliente existente (clientes.id) — up-sell/cross-sell',
  `titulo_oportunidade` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ex: Contrato Laudos TC — Hospital São Lucas',
  `etapa_funil` enum('qualificacao','proposta','negociacao','fechamento') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'qualificacao',
  `valor_estimado` decimal(12,2) DEFAULT NULL,
  `data_fechamento_prevista` date DEFAULT NULL,
  `probabilidade_sucesso` tinyint(3) UNSIGNED DEFAULT NULL COMMENT '0-100 (%)',
  `status_oportunidade` enum('aberta','ganha','perdida') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aberta',
  `motivo_perda` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modalidade_principal` enum('PACS','RIS','HIS','Teleradiologia','RX','USG','CT','MRI','OUTRO') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modalidades_interesse` text COLLATE utf8mb4_unicode_ci,
  `tipo_contrato` enum('laudo_avulso','contrato_mensal','contrato_anual','projeto_implantacao','outro') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `volume_estimado_mes` int(11) DEFAULT NULL COMMENT 'Volume mensal estimado de exames',
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `data_proximo_contato` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `crm_oportunidades`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `crm_oportunidade_modalidades`
--

DROP TABLE IF EXISTS `crm_oportunidade_modalidades`;
CREATE TABLE `crm_oportunidade_modalidades` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `oportunidade_id` int(11) NOT NULL,
  `modalidade` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_contrato` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `volume_estimado_mes` int(11) DEFAULT NULL,
  `observacao` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ordem` tinyint(4) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `crm_oportunidade_modalidades`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `crm_propostas`
--

DROP TABLE IF EXISTS `crm_propostas`;
CREATE TABLE `crm_propostas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL COMMENT 'Tenant (users.id)',
  `numero` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ex: PROP-2026-0001',
  `oportunidade_id` int(11) DEFAULT NULL COMMENT 'Oportunidade de origem',
  `lead_id` int(11) DEFAULT NULL COMMENT 'Lead de origem',
  `cliente_id` int(11) DEFAULT NULL COMMENT 'Cliente existente',
  `cliente_nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cliente_razao_social` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cliente_cnpj_cpf` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cliente_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cliente_telefone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cliente_endereco` text COLLATE utf8mb4_unicode_ci,
  `cliente_cidade` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cliente_estado` char(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cliente_cep` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cliente_responsavel` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `titulo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `validade_proposta` date NOT NULL COMMENT 'Data de validade da proposta',
  `status` enum('gerada','enviada','visualizada','aceita','recusada','expirada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'gerada',
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `desconto_tipo` enum('percentual','fixo') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `desconto_valor` decimal(12,2) DEFAULT '0.00',
  `desconto_total` decimal(12,2) DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `prazo_entrega` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ex: 15 dias úteis',
  `condicao_pagamento` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ex: 50% entrada + 50% na entrega',
  `frete_tipo` enum('cif','fob','sem_frete','a_calcular') COLLATE utf8mb4_unicode_ci DEFAULT 'a_calcular',
  `frete_valor` decimal(10,2) DEFAULT '0.00',
  `local_entrega` text COLLATE utf8mb4_unicode_ci,
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `notas_internas` text COLLATE utf8mb4_unicode_ci COMMENT 'Não aparece no PDF',
  `enviado_em` datetime DEFAULT NULL,
  `visualizado_em` datetime DEFAULT NULL,
  `aceito_em` datetime DEFAULT NULL,
  `recusado_em` datetime DEFAULT NULL,
  `recusado_motivo` text COLLATE utf8mb4_unicode_ci,
  `aceito_por_nome` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aceito_por_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assinatura_tipo` enum('rubrica','nome_digitado','portal') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assinatura_imagem_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pdf_assinado_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pdf_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token_acesso` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Token para link público de aceite',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `pedido_venda_id` int(11) DEFAULT NULL COMMENT 'Pedido de venda gerado ao aceitar'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `crm_propostas`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `crm_proposta_aceite`
--

DROP TABLE IF EXISTS `crm_proposta_aceite`;
CREATE TABLE `crm_proposta_aceite` (
  `id` int(10) UNSIGNED NOT NULL,
  `proposta_id` int(11) NOT NULL,
  `evento` enum('visualizado','aceito','recusado','assinado') COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome_assinante` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assinatura_tipo` enum('rubrica','nome_digitado','portal') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assinatura_imagem_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `motivo_recusa` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `crm_proposta_historico`
--

DROP TABLE IF EXISTS `crm_proposta_historico`;
CREATE TABLE `crm_proposta_historico` (
  `id` int(11) NOT NULL,
  `proposta_id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `status_de` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_para` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `observacao` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `crm_proposta_historico`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `crm_proposta_itens`
--

DROP TABLE IF EXISTS `crm_proposta_itens`;
CREATE TABLE `crm_proposta_itens` (
  `id` int(11) NOT NULL,
  `proposta_id` int(11) NOT NULL,
  `produto_id` int(11) DEFAULT NULL COMMENT 'Referência futura ao módulo de estoque',
  `codigo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descricao` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unidade` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'un',
  `quantidade` decimal(10,3) NOT NULL DEFAULT '1.000',
  `preco_custo` decimal(12,2) DEFAULT '0.00',
  `margem_lucro` decimal(5,2) DEFAULT '0.00' COMMENT 'Percentual de margem',
  `preco_unitario` decimal(12,2) NOT NULL,
  `desconto_item` decimal(5,2) DEFAULT '0.00' COMMENT 'Desconto % por item',
  `total_item` decimal(12,2) NOT NULL,
  `ordem` smallint(6) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `crm_proposta_itens`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `crm_transferencias`
--

DROP TABLE IF EXISTS `crm_transferencias`;
CREATE TABLE `crm_transferencias` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL COMMENT 'Quem executou a transferência (users.id)',
  `related_id` int(11) NOT NULL COMMENT 'ID do lead ou oportunidade',
  `related_type` enum('lead','oportunidade') COLLATE utf8mb4_unicode_ci NOT NULL,
  `de_usuario_id` int(11) NOT NULL COMMENT 'Usuário de origem (users.id)',
  `para_usuario_id` int(11) NOT NULL COMMENT 'Usuário de destino (users.id)',
  `motivo` enum('sdr_qualificacao','conta_chave','colaborador_desligado','rodizio_por_inatividade') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Motivo da transferência',
  `observacao` text COLLATE utf8mb4_unicode_ci COMMENT 'Observação adicional opcional',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Histórico de transferências de leads e oportunidades entre usuários';

-- --------------------------------------------------------

--
-- Estrutura para tabela `dispositivos_controlid`
--

DROP TABLE IF EXISTS `dispositivos_controlid`;
CREATE TABLE `dispositivos_controlid` (
  `id` int(11) NOT NULL,
  `nome` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nome identificador (ex: Leitor UHF Portaria)',
  `modelo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Modelo do equipamento (ex: IDUHF, iDAccess Nano)',
  `tipo` enum('uhf','rfid','facial','biometria','qrcode','outro') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'uhf',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'IP do equipamento na rede local',
  `porta` int(11) NOT NULL DEFAULT '80' COMMENT 'Porta HTTP da API (padrão 80)',
  `usuario_api` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin' COMMENT 'Usuário da API Control ID',
  `senha_api` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Senha da API (armazenada criptografada)',
  `area_instalacao` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Local de instalação (ex: Portaria Principal)',
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `status_online` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1=Online 0=Offline',
  `ultimo_ping` datetime DEFAULT NULL COMMENT 'Último teste de conexão bem-sucedido',
  `session_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Token de sessão ativo na API Control ID',
  `session_expiry` datetime DEFAULT NULL COMMENT 'Expiração do token de sessão',
  `data_cadastro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Leitores Control ID cadastrados no sistema';

-- --------------------------------------------------------

--
-- Estrutura para tabela `dispositivos_controlid_leituras`
--

DROP TABLE IF EXISTS `dispositivos_controlid_leituras`;
CREATE TABLE `dispositivos_controlid_leituras` (
  `id` int(11) NOT NULL,
  `dispositivo_id` int(11) NOT NULL,
  `controlid_log_id` bigint(20) DEFAULT NULL COMMENT 'ID do log no equipamento Control ID',
  `data_hora` datetime NOT NULL COMMENT 'Horário da leitura (do equipamento)',
  `tipo_evento` tinyint(1) DEFAULT NULL COMMENT '6=Acesso concedido, 5=Acesso negado, etc.',
  `tag_value` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Valor da TAG UHF/RFID lida',
  `card_value` bigint(20) DEFAULT NULL COMMENT 'Valor do cartão Wiegand',
  `controlid_user_id` bigint(20) DEFAULT NULL COMMENT 'ID do usuário no Control ID',
  `veiculo_id` int(11) DEFAULT NULL COMMENT 'Veículo identificado no ERP',
  `morador_id` int(11) DEFAULT NULL COMMENT 'Morador vinculado',
  `acesso_liberado` tinyint(1) DEFAULT '0',
  `processado` tinyint(1) DEFAULT '0' COMMENT '1=Já registrado em registros_acesso',
  `data_importacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Leituras de acesso coletadas dos dispositivos Control ID';

-- --------------------------------------------------------

--
-- Estrutura para tabela `dispositivos_controlid_sync_log`
--

DROP TABLE IF EXISTS `dispositivos_controlid_sync_log`;
CREATE TABLE `dispositivos_controlid_sync_log` (
  `id` int(11) NOT NULL,
  `dispositivo_id` int(11) NOT NULL,
  `acao` enum('sincronizar_tags','testar_conexao','criar_usuario','remover_usuario','criar_tag','remover_tag','coletar_logs') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('sucesso','erro','parcial') COLLATE utf8mb4_unicode_ci NOT NULL,
  `detalhes` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON com detalhes da operação',
  `total_enviados` int(11) DEFAULT '0',
  `total_erros` int(11) DEFAULT '0',
  `data_hora` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Log de sincronizações entre ERP e dispositivos Control ID';

-- --------------------------------------------------------

--
-- Estrutura para tabela `email_alertas`
--

DROP TABLE IF EXISTS `email_alertas`;
CREATE TABLE `email_alertas` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `codigo` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Slug único ex: financeiro_contas_vencer_3d',
  `modulo` enum('financeiro','faturamento','crm','corpo_clinico') COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nome legível do alerta',
  `descricao` text COLLATE utf8mb4_unicode_ci COMMENT 'Descrição detalhada do disparo',
  `antecedencia_dias` tinyint(3) UNSIGNED NOT NULL DEFAULT '3' COMMENT 'Dias antes do vencimento para disparar (0 = no dia, negativo = após)',
  `frequencia` enum('unico','diario','semanal') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'diario' COMMENT 'Frequência de reenvio enquanto a condição persistir',
  `hora_disparo` time NOT NULL DEFAULT '08:00:00' COMMENT 'Hora do dia para processar o alerta (via cron)',
  `destinatarios` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'JSON array de e-mails: ["email1","email2"] ou tokens: ["responsavel","vendedor","admin"]',
  `cc` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON array de e-mails em cópia',
  `assunto_template` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Template do assunto com variáveis: {cliente}, {valor}, {dias}, {vencimento}',
  `corpo_template` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Template HTML do corpo com variáveis dinâmicas',
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `ultimo_disparo` datetime DEFAULT NULL,
  `total_disparos` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `email_alertas`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `email_alertas_log`
--

DROP TABLE IF EXISTS `email_alertas_log`;
CREATE TABLE `email_alertas_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `alerta_id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `destinatario` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `assunto` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('enviado','falha','ignorado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'enviado',
  `erro` text COLLATE utf8mb4_unicode_ci,
  `referencia` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ID do registro que gerou o alerta (ex: conta_pagar:42)',
  `disparado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `email_alertas_log`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `empresa_config`
--

DROP TABLE IF EXISTS `empresa_config`;
CREATE TABLE `empresa_config` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo_pessoa` enum('pf','pj') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pj',
  `razao_social` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `nome_fantasia` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cpf_cnpj` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `inscricao_estadual` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `inscricao_municipal` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `email_responsavel` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `email_financeiro` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `financeiro_mesmo_responsavel` tinyint(1) NOT NULL DEFAULT '0',
  `telefone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `site` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cep` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `logradouro` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `numero` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `complemento` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `bairro` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cidade` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `estado` char(2) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `logo_path` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `assinatura_nome` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `assinatura_cargo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `assinatura_rubrica` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `assinatura_imagem_path` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `usar_assinatura_imagem` tinyint(1) NOT NULL DEFAULT '0',
  `autenticacao_texto` text COLLATE utf8mb4_unicode_ci,
  `autenticacao_ativa` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `empresa_config`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `equipamentos_cliente`
--

DROP TABLE IF EXISTS `equipamentos_cliente`;
CREATE TABLE `equipamentos_cliente` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL COMMENT 'Tenant (multi-empresa)',
  `cliente_id` int(11) DEFAULT NULL COMMENT 'FK clientes.id',
  `cliente_nome` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `produto_id` int(11) DEFAULT NULL COMMENT 'FK produtos.id',
  `produto_nome` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `produto_codigo` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `numero_serie` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `modelo` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `marca` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `data_instalacao` date DEFAULT NULL,
  `data_fabricacao` date DEFAULT NULL,
  `vida_util_meses` int(11) DEFAULT NULL COMMENT 'Vida útil em meses vinda do produto',
  `depreciacao_mensal` decimal(12,4) DEFAULT NULL COMMENT 'Depreciação mensal calculada',
  `data_inicio_contador` date DEFAULT NULL COMMENT 'Data em que o contador de vida útil foi iniciado (ao faturar)',
  `data_proxima_troca` date DEFAULT NULL COMMENT 'Calculada: data_inicio_contador + vida_util_meses',
  `observacoes` text COLLATE utf8_unicode_ci,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `equipamentos_cliente`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `especialidades`
--

DROP TABLE IF EXISTS `especialidades`;
CREATE TABLE `especialidades` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `especialidade` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subespecialidade` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rqe` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `especialidades`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `est_movimentacoes`
--

DROP TABLE IF EXISTS `est_movimentacoes`;
CREATE TABLE `est_movimentacoes` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `tipo` enum('entrada','saida','ajuste','transferencia','devolucao_compra','devolucao_venda') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'entrada',
  `origem` enum('manual','xml_nfe','pedido_compra','pedido_venda','ajuste_inventario','devolucao') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `pedido_compra_id` int(11) DEFAULT NULL,
  `pedido_venda_id` int(11) DEFAULT NULL,
  `nfe_chave` varchar(44) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Chave de acesso da NF-e',
  `nfe_numero` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nfe_serie` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nfe_emitente_cnpj` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nfe_emitente_nome` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nfe_data_emissao` date DEFAULT NULL,
  `quantidade` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `unidade` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'UN',
  `preco_unitario` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `valor_total` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `custo_unitario` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `lote` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_fabricacao` date DEFAULT NULL,
  `data_validade` date DEFAULT NULL,
  `localizacao` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estoque_antes` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `estoque_depois` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `motivo` text COLLATE utf8mb4_unicode_ci,
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `usuario_responsavel` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Histórico completo de movimentações de estoque';

--
-- Despejando dados para a tabela `est_movimentacoes`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `est_pedidos_compra`
--

DROP TABLE IF EXISTS `est_pedidos_compra`;
CREATE TABLE `est_pedidos_compra` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `numero` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fornecedor_id` int(11) DEFAULT NULL,
  `fornecedor_nome` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `fornecedor_cnpj` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fornecedor_email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fornecedor_telefone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('rascunho','enviado','confirmado','parcialmente_recebido','recebido','cancelado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'rascunho',
  `data_pedido` date NOT NULL,
  `data_previsao` date DEFAULT NULL,
  `data_recebimento` date DEFAULT NULL,
  `valor_produtos` decimal(15,2) NOT NULL DEFAULT '0.00',
  `valor_frete` decimal(15,2) NOT NULL DEFAULT '0.00',
  `valor_desconto` decimal(15,2) NOT NULL DEFAULT '0.00',
  `valor_total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `nfe_chave` varchar(44) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nfe_numero` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nfe_xml_path` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `condicao_pagamento` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `observacoes_internas` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Pedidos de compra / ordens de compra';

-- --------------------------------------------------------

--
-- Estrutura para tabela `est_pedidos_compra_itens`
--

DROP TABLE IF EXISTS `est_pedidos_compra_itens`;
CREATE TABLE `est_pedidos_compra_itens` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `produto_id` int(11) DEFAULT NULL,
  `codigo_produto` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descricao` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `unidade` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'UN',
  `quantidade` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `quantidade_recebida` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `preco_unitario` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `desconto_perc` decimal(5,2) NOT NULL DEFAULT '0.00',
  `valor_total` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `lote` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_validade` date DEFAULT NULL,
  `observacoes` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `est_pedidos_venda`
--

DROP TABLE IF EXISTS `est_pedidos_venda`;
CREATE TABLE `est_pedidos_venda` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `numero` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `proposta_id` int(11) DEFAULT NULL,
  `oportunidade_id` int(11) DEFAULT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `cliente_nome` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cliente_cpf_cnpj` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cliente_email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cliente_telefone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cliente_endereco` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('rascunho','confirmado','em_separacao','parcialmente_entregue','entregue','faturado','cancelado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'rascunho',
  `data_pedido` date NOT NULL,
  `data_previsao_entrega` date DEFAULT NULL,
  `data_entrega` date DEFAULT NULL,
  `data_faturamento` date DEFAULT NULL,
  `valor_produtos` decimal(15,2) NOT NULL DEFAULT '0.00',
  `valor_frete` decimal(15,2) NOT NULL DEFAULT '0.00',
  `valor_desconto` decimal(15,2) NOT NULL DEFAULT '0.00',
  `valor_total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `valor_custo_total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `margem_total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `condicao_pagamento` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `forma_pagamento` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comissao_percentual` decimal(5,2) NOT NULL DEFAULT '0.00',
  `comissao_valor` decimal(15,2) NOT NULL DEFAULT '0.00',
  `colaborador_id` int(11) DEFAULT NULL,
  `tipo_frete` enum('cif','fob','gratis','valor_fixo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cif',
  `transportadora` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endereco_entrega` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `observacoes_internas` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Pedidos de venda integrados ao estoque e CRM';

--
-- Despejando dados para a tabela `est_pedidos_venda`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `est_pedidos_venda_itens`
--

DROP TABLE IF EXISTS `est_pedidos_venda_itens`;
CREATE TABLE `est_pedidos_venda_itens` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `produto_id` int(11) DEFAULT NULL,
  `codigo_produto` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descricao` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `unidade` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'UN',
  `quantidade` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `quantidade_entregue` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `preco_unitario` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `preco_custo` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `desconto_perc` decimal(5,2) NOT NULL DEFAULT '0.00',
  `valor_total` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `margem_item` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `lote` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observacoes` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `est_pedidos_venda_itens`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `est_pedido_seq`
--

DROP TABLE IF EXISTS `est_pedido_seq`;
CREATE TABLE `est_pedido_seq` (
  `usuario_id` int(11) NOT NULL,
  `tipo` enum('compra','venda') COLLATE utf8mb4_unicode_ci NOT NULL,
  `ultimo_seq` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `est_pedido_seq`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `fornecedores`
--

DROP TABLE IF EXISTS `fornecedores`;
CREATE TABLE `fornecedores` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo` enum('PJ','PF') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PJ',
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome_fantasia` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `documento` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `celular` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contato_nome` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cep` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endereco` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `complemento` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bairro` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cidade` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` char(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inscricao_estadual` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inscricao_municipal` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prazo_pagamento` smallint(5) UNSIGNED DEFAULT NULL,
  `cnae_principal` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descricao_cnae` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `status` enum('ativo','inativo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ativo',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `fornecedores`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `historico_importacoes_ofx`
--

DROP TABLE IF EXISTS `historico_importacoes_ofx`;
CREATE TABLE `historico_importacoes_ofx` (
  `id` int(10) UNSIGNED NOT NULL,
  `conta_id` int(10) UNSIGNED NOT NULL,
  `nome_arquivo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `banco_id_ofx` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'BANKID do arquivo OFX',
  `acct_id_ofx` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ACCTID do arquivo OFX',
  `dt_inicio_ofx` date DEFAULT NULL COMMENT 'DTSTART do arquivo OFX',
  `dt_fim_ofx` date DEFAULT NULL COMMENT 'DTEND do arquivo OFX',
  `ultimo_fitid` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'FITID da última transação importada',
  `ultima_data` date DEFAULT NULL COMMENT 'Data da última transação importada',
  `total_transacoes` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Total de transações no arquivo',
  `importadas` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Novas transações importadas',
  `duplicadas` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Transações já existentes (ignoradas)',
  `saldo_final_ofx` decimal(15,2) DEFAULT NULL COMMENT 'BALAMT do arquivo OFX',
  `importado_por` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Usuário que fez a importação',
  `importado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Histórico de importações OFX por conta bancária';

-- --------------------------------------------------------

--
-- Estrutura para tabela `hub_ia_agentes`
--

DROP TABLE IF EXISTS `hub_ia_agentes`;
CREATE TABLE `hub_ia_agentes` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '?',
  `descricao` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `conector_id` int(11) DEFAULT NULL,
  `prompt_id` int(11) DEFAULT NULL,
  `prompt_base` mediumtext COLLATE utf8mb4_unicode_ci COMMENT 'Usado quando prompt_id é nulo (prompt inline)',
  `temperatura` decimal(3,2) DEFAULT NULL,
  `idioma` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pt-BR',
  `personalidade` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `permite_consulta_banco` tinyint(1) NOT NULL DEFAULT '0',
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `hub_ia_agente_permissoes`
--

DROP TABLE IF EXISTS `hub_ia_agente_permissoes`;
CREATE TABLE `hub_ia_agente_permissoes` (
  `id` int(11) NOT NULL,
  `agente_id` int(11) NOT NULL,
  `modulo` enum('crm','financeiro','rdv','marketing','cnes','estoque','rh','configuracoes') COLLATE utf8mb4_unicode_ci NOT NULL,
  `permitido` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `hub_ia_banco_config`
--

DROP TABLE IF EXISTS `hub_ia_banco_config`;
CREATE TABLE `hub_ia_banco_config` (
  `id` int(11) NOT NULL,
  `tabelas_liberadas` mediumtext COLLATE utf8mb4_unicode_ci COMMENT 'JSON array de nomes de tabelas/views liberadas para consulta via IA',
  `ativo` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `hub_ia_conectores`
--

DROP TABLE IF EXISTS `hub_ia_conectores`;
CREATE TABLE `hub_ia_conectores` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nome de exibição, ex: OpenAI Produção',
  `provider` enum('openai','claude','gemini','deepseek','mistral','ollama') COLLATE utf8mb4_unicode_ci NOT NULL,
  `api_key_enc` text COLLATE utf8mb4_unicode_ci COMMENT 'Chave de API criptografada (CryptoService)',
  `endpoint` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Base URL customizada (obrigatório para ollama/local)',
  `modelo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Ex: gpt-4o-mini, claude-3-5-sonnet-20241022',
  `temperatura` decimal(3,2) NOT NULL DEFAULT '0.30',
  `max_tokens` int(11) NOT NULL DEFAULT '2000',
  `timeout_segundos` int(11) NOT NULL DEFAULT '30',
  `status` enum('ativo','inativo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ativo',
  `ultimo_teste_em` datetime DEFAULT NULL,
  `ultimo_teste_status` enum('ok','erro') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ultimo_teste_mensagem` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `hub_ia_conhecimento_chunks`
--

DROP TABLE IF EXISTS `hub_ia_conhecimento_chunks`;
CREATE TABLE `hub_ia_conhecimento_chunks` (
  `id` int(11) NOT NULL,
  `documento_id` int(11) NOT NULL,
  `ordem` int(11) NOT NULL DEFAULT '0',
  `conteudo` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `embedding` longtext COLLATE utf8mb4_unicode_ci COMMENT 'JSON array de floats',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `hub_ia_conhecimento_documentos`
--

DROP TABLE IF EXISTS `hub_ia_conhecimento_documentos`;
CREATE TABLE `hub_ia_conhecimento_documentos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nome_original` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'pdf, docx, xlsx, txt',
  `categoria` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tamanho_bytes` int(11) NOT NULL DEFAULT '0',
  `status` enum('processando','pronto','erro') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'processando',
  `mensagem_erro` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_chunks` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `hub_ia_historico`
--

DROP TABLE IF EXISTS `hub_ia_historico`;
CREATE TABLE `hub_ia_historico` (
  `id` int(11) NOT NULL,
  `agente_id` int(11) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `modulo_origem` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'hub_ia',
  `pergunta` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `resposta` mediumtext COLLATE utf8mb4_unicode_ci,
  `sql_gerado` mediumtext COLLATE utf8mb4_unicode_ci,
  `sql_linhas_retornadas` int(11) DEFAULT NULL,
  `provider` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modelo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tokens_prompt` int(11) DEFAULT NULL,
  `tokens_resposta` int(11) DEFAULT NULL,
  `tokens_total` int(11) DEFAULT NULL,
  `custo_estimado_usd` decimal(10,6) DEFAULT NULL,
  `tempo_ms` int(11) DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('sucesso','erro') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sucesso',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `hub_ia_logs`
--

DROP TABLE IF EXISTS `hub_ia_logs`;
CREATE TABLE `hub_ia_logs` (
  `id` int(11) NOT NULL,
  `conector_id` int(11) DEFAULT NULL,
  `agente_id` int(11) DEFAULT NULL,
  `historico_id` int(11) DEFAULT NULL,
  `provider` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_http` int(11) DEFAULT NULL,
  `erro` text COLLATE utf8mb4_unicode_ci,
  `tempo_ms` int(11) DEFAULT NULL,
  `tokens_total` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `hub_ia_prompts`
--

DROP TABLE IF EXISTS `hub_ia_prompts`;
CREATE TABLE `hub_ia_prompts` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nome` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `categoria` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `conteudo` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `hub_ia_whatsapp_config`
--

DROP TABLE IF EXISTS `hub_ia_whatsapp_config`;
CREATE TABLE `hub_ia_whatsapp_config` (
  `id` int(11) NOT NULL,
  `numero` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token_enc` text COLLATE utf8mb4_unicode_ci,
  `webhook_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('desconectado','conectado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'desconectado',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `integracoes`
--

DROP TABLE IF EXISTS `integracoes`;
CREATE TABLE `integracoes` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('API','Webhook','Fiscal','Financeira') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('ativo','inativo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ativo',
  `config_json` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `integracoes`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `integracoes_logs`
--

DROP TABLE IF EXISTS `integracoes_logs`;
CREATE TABLE `integracoes_logs` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `integracao_id` int(11) NOT NULL,
  `evento` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('sucesso','falha') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sucesso',
  `details` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `layout_exames`
--

DROP TABLE IF EXISTS `layout_exames`;
CREATE TABLE `layout_exames` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nome do layout (ex: PACS Tasy, RIS Pixeon)',
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `formato` enum('xlsx','csv','xml') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'xlsx',
  `mapeamento_json` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'JSON com mapeamento de colunas do arquivo para campos do sistema',
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `separador` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ';',
  `linha_cabecalho` tinyint(4) NOT NULL DEFAULT '1',
  `col_medico` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `col_crm` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `col_modalidade` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `col_study_description` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `col_prioridade` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `col_data_conclusao` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `col_paciente` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `col_paciente_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `col_unidade` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `col_accession` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `col_convenio` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `col_valor_exame` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `col_revisor` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `col_data_revisao` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `valores_urgencia` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'URGENTE,U,URGENT',
  `formato_data` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'd/m/Y H:i'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `layout_exames`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `manual_artigos`
--

DROP TABLE IF EXISTS `manual_artigos`;
CREATE TABLE `manual_artigos` (
  `id` int(10) UNSIGNED NOT NULL,
  `categoria_id` int(10) UNSIGNED NOT NULL,
  `slug` varchar(120) COLLATE utf8_unicode_ci NOT NULL,
  `titulo` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `resumo` varchar(400) COLLATE utf8_unicode_ci DEFAULT NULL,
  `conteudo` longtext COLLATE utf8_unicode_ci NOT NULL,
  `ordem` smallint(6) NOT NULL DEFAULT '0',
  `publicado` tinyint(1) NOT NULL DEFAULT '1',
  `criado_por` int(10) UNSIGNED DEFAULT NULL,
  `atualizado_por` int(10) UNSIGNED DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `manual_artigos`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `manual_categorias`
--

DROP TABLE IF EXISTS `manual_categorias`;
CREATE TABLE `manual_categorias` (
  `id` int(10) UNSIGNED NOT NULL,
  `slug` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `titulo` varchar(120) COLLATE utf8_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8_unicode_ci,
  `icone` varchar(60) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'fas fa-book',
  `cor` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '#1e40af',
  `ordem` smallint(6) NOT NULL DEFAULT '0',
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `manual_categorias`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `manual_historico`
--

DROP TABLE IF EXISTS `manual_historico`;
CREATE TABLE `manual_historico` (
  `id` int(10) UNSIGNED NOT NULL,
  `artigo_id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED DEFAULT NULL,
  `conteudo` longtext COLLATE utf8_unicode_ci NOT NULL,
  `titulo` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `manut_ordens_servico`
--

DROP TABLE IF EXISTS `manut_ordens_servico`;
CREATE TABLE `manut_ordens_servico` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL COMMENT 'Tenant (multi-empresa)',
  `numero` varchar(20) COLLATE utf8_unicode_ci NOT NULL COMMENT 'OS-2026-00001',
  `tipo` enum('preventiva','corretiva') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'corretiva',
  `status` enum('aberta','em_andamento','aguardando_peca','concluida','faturada','cancelada') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'aberta',
  `cliente_id` int(11) DEFAULT NULL,
  `cliente_nome` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `cliente_cpf_cnpj` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cliente_email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cliente_telefone` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cliente_endereco` text COLLATE utf8_unicode_ci,
  `cliente_cidade` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cliente_estado` char(2) COLLATE utf8_unicode_ci DEFAULT NULL,
  `equipamento_id` int(11) DEFAULT NULL COMMENT 'FK equipamentos_cliente.id',
  `produto_id` int(11) DEFAULT NULL COMMENT 'Produto/equipamento principal',
  `produto_nome` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `produto_codigo` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `numero_serie` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `marca` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Marca do equipamento (importada do cadastro de produtos)',
  `modelo` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Modelo do equipamento (importado do cadastro de produtos)',
  `vida_util_meses` int(11) DEFAULT NULL COMMENT 'Vida útil em meses (importada do cadastro de produtos)',
  `motivo_chamado` text COLLATE utf8_unicode_ci NOT NULL COMMENT 'Descrição do motivo do chamado',
  `descricao_servico` text COLLATE utf8_unicode_ci COMMENT 'Descrição do serviço a realizar',
  `evolucao` text COLLATE utf8_unicode_ci COMMENT 'Evolução da manutenção — preenchido durante o atendimento',
  `data_abertura` date NOT NULL,
  `data_previsao` date DEFAULT NULL,
  `data_conclusao` date DEFAULT NULL,
  `tecnico_responsavel` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `prioridade` enum('baixa','normal','alta','urgente') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'normal',
  `valor_servico` decimal(12,2) NOT NULL DEFAULT '0.00',
  `valor_pecas` decimal(12,2) NOT NULL DEFAULT '0.00',
  `valor_total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `proposta_id` int(11) DEFAULT NULL COMMENT 'FK crm_propostas.id — proposta gerada ao criar OS',
  `pedido_venda_id` int(11) DEFAULT NULL COMMENT 'FK est_pedidos_venda.id — gerado ao aceitar proposta',
  `conta_receber_id` int(11) DEFAULT NULL COMMENT 'FK contasreceber.id — gerado ao faturar',
  `observacoes` text COLLATE utf8_unicode_ci,
  `token_impressao` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Token para impressão pública',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `manut_ordens_servico`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `manut_os_historico`
--

DROP TABLE IF EXISTS `manut_os_historico`;
CREATE TABLE `manut_os_historico` (
  `id` int(11) NOT NULL,
  `os_id` int(11) NOT NULL COMMENT 'FK manut_ordens_servico.id',
  `usuario_id` int(11) NOT NULL,
  `usuario_nome` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `status_anterior` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `status_novo` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `descricao` text COLLATE utf8_unicode_ci NOT NULL COMMENT 'Descrição da evolução ou mudança',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `manut_os_historico`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `manut_os_seq`
--

DROP TABLE IF EXISTS `manut_os_seq`;
CREATE TABLE `manut_os_seq` (
  `usuario_id` int(11) NOT NULL,
  `ano` year(4) NOT NULL,
  `ultimo_numero` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `manut_os_seq`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `manut_os_trocas`
--

DROP TABLE IF EXISTS `manut_os_trocas`;
CREATE TABLE `manut_os_trocas` (
  `id` int(11) NOT NULL,
  `os_id` int(11) NOT NULL COMMENT 'FK manut_ordens_servico.id',
  `produto_id` int(11) DEFAULT NULL COMMENT 'FK produtos.id',
  `produto_codigo` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `descricao` varchar(500) COLLATE utf8_unicode_ci NOT NULL COMMENT 'O que foi trocado/feito',
  `unidade` varchar(20) COLLATE utf8_unicode_ci DEFAULT 'UN',
  `quantidade` decimal(10,3) NOT NULL DEFAULT '1.000',
  `preco_unitario` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `preco_total` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `vida_util_meses` int(11) DEFAULT NULL COMMENT 'Vida útil da peça trocada (para calcular próxima troca)',
  `data_proxima_troca` date DEFAULT NULL COMMENT 'Calculada: data_conclusao + vida_util_meses',
  `observacoes` varchar(500) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `manut_os_trocas`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `marketing_campanhas`
--

DROP TABLE IF EXISTS `marketing_campanhas`;
CREATE TABLE `marketing_campanhas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL COMMENT 'Tenant (users.id)',
  `nome` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Nome da campanha',
  `descricao` text COLLATE utf8_unicode_ci COMMENT 'Descrição interna da campanha',
  `canal` enum('email','whatsapp','telegram','sdr') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'email' COMMENT 'Canal de envio',
  `status` enum('rascunho','ativa','pausada','arquivada') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'rascunho',
  `assunto_email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Assunto do e-mail',
  `tipo_conteudo` enum('texto','html') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'html' COMMENT 'Tipo de conteúdo do corpo',
  `corpo` longtext COLLATE utf8_unicode_ci COMMENT 'Corpo do e-mail (texto ou HTML) / mensagem WhatsApp/Telegram/SDR',
  `remetente_nome` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Nome do remetente (email)',
  `remetente_email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'E-mail do remetente',
  `numero_origem` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Número ou token de origem (WhatsApp/Telegram)',
  `total_enviados` int(11) NOT NULL DEFAULT '0',
  `total_abertos` int(11) NOT NULL DEFAULT '0',
  `total_cliques` int(11) NOT NULL DEFAULT '0',
  `total_erros` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `marketing_campanhas`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `marketing_disparadores`
--

DROP TABLE IF EXISTS `marketing_disparadores`;
CREATE TABLE `marketing_disparadores` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL COMMENT 'Tenant (users.id)',
  `campanha_id` int(11) NOT NULL COMMENT 'marketing_campanhas.id',
  `nome` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Nome/identificação do disparo',
  `publico` enum('clientes','leads','oportunidades') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'leads' COMMENT 'Público-alvo',
  `segmentacao` text COLLATE utf8_unicode_ci COMMENT 'JSON com filtros de segmentação aplicados',
  `total_destinatarios` int(11) NOT NULL DEFAULT '0' COMMENT 'Total de destinatários no momento do disparo',
  `status` enum('rascunho','agendado','em_andamento','concluido','pausado','cancelado') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'rascunho',
  `agendado_para` datetime DEFAULT NULL COMMENT 'Data/hora agendada para disparo automático',
  `iniciado_em` datetime DEFAULT NULL,
  `concluido_em` datetime DEFAULT NULL,
  `intervalo_envio` int(11) NOT NULL DEFAULT '5' COMMENT 'Intervalo em segundos entre envios em lote',
  `lote_tamanho` int(11) NOT NULL DEFAULT '5' COMMENT 'Quantidade de envios por lote',
  `total_enviados` int(11) NOT NULL DEFAULT '0',
  `total_erros` int(11) NOT NULL DEFAULT '0',
  `log_execucao` text COLLATE utf8_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `marketing_disparadores`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `marketing_envios`
--

DROP TABLE IF EXISTS `marketing_envios`;
CREATE TABLE `marketing_envios` (
  `id` int(11) NOT NULL,
  `disparador_id` int(11) NOT NULL COMMENT 'marketing_disparadores.id',
  `usuario_id` int(11) NOT NULL COMMENT 'Tenant (users.id)',
  `destinatario_tipo` enum('cliente','lead','oportunidade') COLLATE utf8_unicode_ci NOT NULL,
  `destinatario_id` int(11) NOT NULL COMMENT 'ID do registro de origem',
  `destinatario_nome` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `destinatario_email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `destinatario_tel` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` enum('pendente','enviado','erro','aberto','clicado','descadastrado') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'pendente',
  `erro_msg` text COLLATE utf8_unicode_ci COMMENT 'Mensagem de erro se falhou',
  `enviado_em` datetime DEFAULT NULL,
  `aberto_em` datetime DEFAULT NULL,
  `clicado_em` datetime DEFAULT NULL,
  `tracking_token` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Token único para rastrear abertura/clique',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `marketing_envios`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `marketing_interacoes_crm`
--

DROP TABLE IF EXISTS `marketing_interacoes_crm`;
CREATE TABLE `marketing_interacoes_crm` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `envio_id` int(11) NOT NULL COMMENT 'marketing_envios.id',
  `campanha_id` int(11) NOT NULL COMMENT 'marketing_campanhas.id',
  `related_type` enum('lead','oportunidade','cliente') COLLATE utf8_unicode_ci NOT NULL,
  `related_id` int(11) NOT NULL,
  `evento` enum('enviado','aberto','clicado','respondido','convertido','descadastrado') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'enviado',
  `observacao` text COLLATE utf8_unicode_ci,
  `ocorrido_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `marketing_interacoes_crm`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `medicos`
--

DROP TABLE IF EXISTS `medicos`;
CREATE TABLE `medicos` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `crm` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `uf_crm` char(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cpf` varchar(14) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefone` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `especialidade_id` int(10) UNSIGNED DEFAULT NULL,
  `subespecialidade` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rqe` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assinatura_digital` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('ativo','inativo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ativo',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `medicos`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `medico_crms`
--

DROP TABLE IF EXISTS `medico_crms`;
CREATE TABLE `medico_crms` (
  `id` int(10) UNSIGNED NOT NULL,
  `medico_id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `crm` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `uf_crm` char(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `principal` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = CRM principal (espelho do medicos.crm)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `medico_crms`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `medico_exames`
--

DROP TABLE IF EXISTS `medico_exames`;
CREATE TABLE `medico_exames` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `medico_id` int(10) UNSIGNED NOT NULL,
  `tabela_exame_id` int(10) UNSIGNED NOT NULL,
  `valor_rotina` decimal(10,2) NOT NULL DEFAULT '0.00',
  `valor_urgencia` decimal(10,2) NOT NULL DEFAULT '0.00',
  `usa_valor_custom` tinyint(1) NOT NULL DEFAULT '0',
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `medico_exames`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `mkt_campanhas`
--

DROP TABLE IF EXISTS `mkt_campanhas`;
CREATE TABLE `mkt_campanhas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `publico` enum('clientes','leads','oportunidades') COLLATE utf8mb4_unicode_ci NOT NULL,
  `canal` enum('email','whatsapp','telegram','instagram','facebook') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'email',
  `assunto` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `corpo_html` mediumtext COLLATE utf8mb4_unicode_ci,
  `tem_anexo` tinyint(1) DEFAULT '0',
  `anexo_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `anexo_nome` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('rascunho','publicada','disparando','concluida','pausada','cancelada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'rascunho',
  `total_contatos` int(11) DEFAULT '0',
  `total_enviados` int(11) DEFAULT '0',
  `total_abertos` int(11) DEFAULT '0',
  `total_erros` int(11) DEFAULT '0',
  `publicada_em` datetime DEFAULT NULL,
  `criada_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizada_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `mkt_campanha_contatos`
--

DROP TABLE IF EXISTS `mkt_campanha_contatos`;
CREATE TABLE `mkt_campanha_contatos` (
  `id` int(11) NOT NULL,
  `campanha_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `referencia_tipo` enum('cliente','lead','oportunidade') COLLATE utf8mb4_unicode_ci NOT NULL,
  `referencia_id` int(11) NOT NULL,
  `status` enum('pendente','enviado','erro','aberto') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendente',
  `enviado_em` datetime DEFAULT NULL,
  `aberto_em` datetime DEFAULT NULL,
  `erro_msg` text COLLATE utf8mb4_unicode_ci,
  `token_rastreio` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `movimentacoes_bancarias`
--

DROP TABLE IF EXISTS `movimentacoes_bancarias`;
CREATE TABLE `movimentacoes_bancarias` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `conta_id` int(10) UNSIGNED NOT NULL,
  `fitid` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ID único OFX (FITID) — NULL para lançamentos manuais',
  `tipo` enum('credito','debito') COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` decimal(15,2) NOT NULL COMMENT 'Sempre positivo; tipo indica crédito/débito',
  `data_lancamento` date NOT NULL,
  `descricao` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `checknum` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Número do cheque/documento (CHECKNUM do OFX)',
  `categoria` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Categoria manual para relatórios',
  `conciliado` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=pendente, 1=conciliado',
  `origem` enum('ofx','manual','importacao') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ofx',
  `importacao_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'FK para historico_importacoes_ofx',
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Movimentações bancárias (OFX + manuais)';

-- --------------------------------------------------------

--
-- Estrutura para tabela `notas_fiscais`
--

DROP TABLE IF EXISTS `notas_fiscais`;
CREATE TABLE `notas_fiscais` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `numero_nf` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `serie` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor_total` decimal(15,2) NOT NULL,
  `data_emissao` date NOT NULL,
  `status` enum('rascunho','emitida','cancelada','importada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'rascunho',
  `xml_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `asaas_invoice_id` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `origem_emissao` enum('manual','asaas') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `conta_receber_id` int(10) UNSIGNED DEFAULT NULL,
  `asaas_pdf_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `asaas_xml_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `asaas_error_desc` text COLLATE utf8mb4_unicode_ci,
  `asaas_status` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `asaas_erro_mensagem` text COLLATE utf8mb4_unicode_ci COMMENT 'Mensagem de erro retornada pelo Asaas ao tentar emitir a NF',
  `servico_descricao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `servico_codigo` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `servico_id_asaas` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observacoes_nf` text COLLATE utf8mb4_unicode_ci,
  `portal_liberada` tinyint(1) NOT NULL DEFAULT '0',
  `portal_liberada_em` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `notas_fiscais`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `notas_fiscais_anexos`
--

DROP TABLE IF EXISTS `notas_fiscais_anexos`;
CREATE TABLE `notas_fiscais_anexos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nota_fiscal_id` int(11) NOT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `notas_fiscais_anexos`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `notas_fiscais_importacoes`
--

DROP TABLE IF EXISTS `notas_fiscais_importacoes`;
CREATE TABLE `notas_fiscais_importacoes` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `arquivo_xml_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('sucesso','falha') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sucesso',
  `mensagem` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `notas_fiscais_importacoes`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `notificacao_config_alertas`
--

DROP TABLE IF EXISTS `notificacao_config_alertas`;
CREATE TABLE `notificacao_config_alertas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL COMMENT 'Usuário proprietário da configuração',
  `tipo` varchar(60) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Código do tipo de alerta',
  `ativo` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=ativo, 0=desativado',
  `dias_antecedencia` int(3) NOT NULL DEFAULT '3' COMMENT 'Quantos dias antes do vencimento gerar alerta',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Configuração de alertas de notificação por usuário';

--
-- Despejando dados para a tabela `notificacao_config_alertas`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `notificacoes`
--

DROP TABLE IF EXISTS `notificacoes`;
CREATE TABLE `notificacoes` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL COMMENT 'Usuário destinatário',
  `tipo` varchar(60) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Código do tipo: crm_retorno_vencendo, conta_pagar_vencendo, etc.',
  `titulo` varchar(200) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Título curto da notificação',
  `mensagem` text COLLATE utf8_unicode_ci COMMENT 'Texto completo da notificação',
  `link` varchar(500) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'URL de destino ao clicar',
  `icone` varchar(80) COLLATE utf8_unicode_ci DEFAULT 'fas fa-bell' COMMENT 'Classe FontAwesome do ícone',
  `cor` varchar(20) COLLATE utf8_unicode_ci DEFAULT 'primary' COMMENT 'Cor Bootstrap: primary, warning, danger, success, info',
  `lida` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=não lida, 1=lida',
  `lida_em` datetime DEFAULT NULL COMMENT 'Quando foi marcada como lida',
  `referencia_tipo` varchar(60) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Tipo do objeto de referência: oportunidade, conta_pagar, etc.',
  `referencia_id` int(11) DEFAULT NULL COMMENT 'ID do objeto de referência',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Notificações do sistema por usuário';

-- --------------------------------------------------------

--
-- Estrutura para tabela `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `password_reset_tokens`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `plano_contas`
--

DROP TABLE IF EXISTS `plano_contas`;
CREATE TABLE `plano_contas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `codigo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('Receita','Despesa') COLLATE utf8mb4_unicode_ci NOT NULL,
  `nivel` int(11) NOT NULL DEFAULT '1',
  `conta_pai_id` int(11) DEFAULT NULL,
  `status` enum('ativo','inativo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ativo',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `plano_contas`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `portal_clientes`
--

DROP TABLE IF EXISTS `portal_clientes`;
CREATE TABLE `portal_clientes` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'NULL = primeiro acesso ainda não realizado',
  `primeiro_acesso` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1 = ainda não definiu senha',
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `ultimo_acesso` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `portal_clientes`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `portal_clientes_tokens`
--

DROP TABLE IF EXISTS `portal_clientes_tokens`;
CREATE TABLE `portal_clientes_tokens` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `token` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('primeiro_acesso','reset_senha') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'primeiro_acesso',
  `usado` tinyint(1) NOT NULL DEFAULT '0',
  `expira_em` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `portal_clientes_tokens`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `produtos`
--

DROP TABLE IF EXISTS `produtos`;
CREATE TABLE `produtos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL COMMENT 'Dono/tenant do registro',
  `codigo` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Código incremental ex: PRD-00001',
  `tipo` enum('produto','servico') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'produto',
  `categoria` enum('equipamento_medico','equipamento_hospitalar','consumivel','reagente','software','servico_manutencao','servico_instalacao','servico_treinamento','servico_consultoria','acessorio','peca_reposicao','outro') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'equipamento_medico',
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome_tecnico` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nome técnico/científico do produto',
  `descricao_curta` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Resumo para listagens e propostas',
  `descricao_completa` text COLLATE utf8mb4_unicode_ci COMMENT 'Descrição detalhada para catálogo',
  `modelo` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `marca` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fabricante_id` int(11) DEFAULT NULL COMMENT 'FK fornecedores.id',
  `fabricante_nome` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Cache do nome do fabricante',
  `pais_origem` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ncm` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nomenclatura Comum do Mercosul',
  `anvisa_registro` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Número de registro ANVISA',
  `anvisa_classe` enum('I','II','III','IV') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Classe de risco ANVISA',
  `anvisa_validade` date DEFAULT NULL COMMENT 'Validade do registro ANVISA',
  `unidade_medida` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'UN' COMMENT 'UN, KG, L, M, CX, KIT…',
  `unidade_compra` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Unidade de compra (ex: CX c/ 10)',
  `fator_conversao` decimal(10,4) DEFAULT '1.0000' COMMENT 'Qtd de UN por unidade de compra',
  `preco_custo` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `preco_custo_medio` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'Custo médio ponderado',
  `despesas_acessorias` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'Frete, seguro, impostos de entrada',
  `custo_total` decimal(15,4) GENERATED ALWAYS AS ((`preco_custo` + `despesas_acessorias`)) STORED,
  `markup_percentual` decimal(10,4) NOT NULL DEFAULT '0.0000' COMMENT 'Markup sobre custo total (%)',
  `preco_venda` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'Preço de venda praticado',
  `preco_minimo_venda` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'Piso de venda (não vender abaixo)',
  `preco_sugerido` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'Calculado: custo_total * (1 + markup/100)',
  `margem_lucro_bruta` decimal(10,4) GENERATED ALWAYS AS ((case when (`preco_venda` > 0) then (((`preco_venda` - `preco_custo`) / `preco_venda`) * 100) else 0 end)) STORED COMMENT 'Margem bruta % sobre preço de venda',
  `margem_lucro_liquida` decimal(10,4) NOT NULL DEFAULT '0.0000' COMMENT 'Margem após impostos/comissões (manual)',
  `impostos_percentual` decimal(10,4) NOT NULL DEFAULT '0.0000' COMMENT 'Alíquota total de impostos sobre venda (%)',
  `moeda` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'BRL',
  `controla_estoque` tinyint(1) NOT NULL DEFAULT '1',
  `estoque_atual` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `estoque_minimo` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'Gatilho de alerta de reposição',
  `estoque_maximo` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `ponto_reposicao` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'Qtd para disparar pedido de compra',
  `lead_time_dias` int(11) NOT NULL DEFAULT '0' COMMENT 'Prazo médio de entrega do fornecedor (dias)',
  `localizacao_estoque` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Prateleira, corredor, depósito',
  `controla_validade` tinyint(1) NOT NULL DEFAULT '0',
  `alerta_validade_dias` int(11) NOT NULL DEFAULT '90' COMMENT 'Dias antes do vencimento para alertar',
  `lote_obrigatorio` tinyint(1) NOT NULL DEFAULT '0',
  `controla_depreciacao` tinyint(1) NOT NULL DEFAULT '0',
  `vida_util_meses` int(11) DEFAULT NULL COMMENT 'Vida útil em meses (ex: 60 = 5 anos)',
  `valor_residual` decimal(15,4) DEFAULT '0.0000' COMMENT 'Valor ao final da vida útil',
  `metodo_depreciacao` enum('linear','soma_digitos','unidades_produzidas') COLLATE utf8mb4_unicode_ci DEFAULT 'linear',
  `depreciacao_mensal` decimal(15,4) DEFAULT '0.0000' COMMENT 'Valor mensal calculado',
  `alerta_substituicao_meses` int(11) DEFAULT NULL COMMENT 'Meses antes do fim da vida útil para sugerir troca',
  `peso_kg` decimal(10,4) DEFAULT NULL,
  `altura_cm` decimal(10,4) DEFAULT NULL,
  `largura_cm` decimal(10,4) DEFAULT NULL,
  `profundidade_cm` decimal(10,4) DEFAULT NULL,
  `voltagem` enum('110V','220V','bivolt','DC','N/A') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `potencia_w` decimal(10,2) DEFAULT NULL,
  `garantia_meses` int(11) NOT NULL DEFAULT '0',
  `garantia_estendida_meses` int(11) NOT NULL DEFAULT '0',
  `assistencia_tecnica` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nome/contato da assistência técnica',
  `manual_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ficha_tecnica_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `palavras_chave` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Tags para busca e IA',
  `publico_alvo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ex: UTI, Laboratório, Clínica Geral',
  `indicacoes_uso` text COLLATE utf8mb4_unicode_ci COMMENT 'Indicações clínicas',
  `contraindicacoes` text COLLATE utf8mb4_unicode_ci,
  `diferenciais` text COLLATE utf8mb4_unicode_ci COMMENT 'Diferenciais competitivos',
  `concorrentes` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Produtos concorrentes (para IA de precificação)',
  `score_venda` tinyint(3) UNSIGNED DEFAULT '0' COMMENT '0-100: score de facilidade de venda (IA)',
  `ciclo_venda_dias` int(11) DEFAULT NULL COMMENT 'Ciclo médio de venda em dias',
  `taxa_conversao` decimal(5,2) DEFAULT NULL COMMENT '% de propostas que viram venda',
  `ultima_venda_em` date DEFAULT NULL,
  `total_vendido` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'Quantidade total vendida (histórico)',
  `receita_total` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'Receita total gerada',
  `imagem_principal` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `imagens_adicionais` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON array de paths',
  `video_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `catalogo_pdf_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('ativo','inativo','descontinuado','em_homologacao') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ativo',
  `visivel_proposta` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Aparece na seleção de itens de proposta',
  `visivel_catalogo` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Aparece no catálogo público',
  `requer_instalacao` tinyint(1) NOT NULL DEFAULT '0',
  `requer_treinamento` tinyint(1) NOT NULL DEFAULT '0',
  `requer_anvisa` tinyint(1) NOT NULL DEFAULT '0',
  `observacoes_internas` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catálogo de produtos e serviços do ERP InLaudo';

--
-- Despejando dados para a tabela `produtos`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `produtos_bkp_20260604`
--

DROP TABLE IF EXISTS `produtos_bkp_20260604`;
CREATE TABLE `produtos_bkp_20260604` (
  `id` int(11) NOT NULL DEFAULT '0',
  `usuario_id` int(11) NOT NULL COMMENT 'Dono/tenant do registro',
  `codigo` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Código incremental ex: PRD-00001',
  `tipo` enum('produto','servico') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'produto',
  `categoria` enum('equipamento_medico','equipamento_hospitalar','consumivel','reagente','software','servico_manutencao','servico_instalacao','servico_treinamento','servico_consultoria','acessorio','peca_reposicao','outro') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'equipamento_medico',
  `nome` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome_tecnico` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nome técnico/científico do produto',
  `descricao_curta` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Resumo para listagens e propostas',
  `descricao_completa` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Descrição detalhada para catálogo',
  `modelo` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `marca` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fabricante_id` int(11) DEFAULT NULL COMMENT 'FK fornecedores.id',
  `fabricante_nome` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Cache do nome do fabricante',
  `pais_origem` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ncm` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nomenclatura Comum do Mercosul',
  `anvisa_registro` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Número de registro ANVISA',
  `anvisa_classe` enum('I','II','III','IV') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Classe de risco ANVISA',
  `anvisa_validade` date DEFAULT NULL COMMENT 'Validade do registro ANVISA',
  `unidade_medida` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'UN' COMMENT 'UN, KG, L, M, CX, KIT…',
  `unidade_compra` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Unidade de compra (ex: CX c/ 10)',
  `fator_conversao` decimal(10,4) DEFAULT '1.0000' COMMENT 'Qtd de UN por unidade de compra',
  `preco_custo` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `preco_custo_medio` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'Custo médio ponderado',
  `despesas_acessorias` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'Frete, seguro, impostos de entrada',
  `custo_total` decimal(15,4) DEFAULT NULL,
  `markup_percentual` decimal(10,4) NOT NULL DEFAULT '0.0000' COMMENT 'Markup sobre custo total (%)',
  `preco_venda` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'Preço de venda praticado',
  `preco_minimo_venda` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'Piso de venda (não vender abaixo)',
  `preco_sugerido` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'Calculado: custo_total * (1 + markup/100)',
  `margem_lucro_bruta` decimal(10,4) DEFAULT NULL COMMENT 'Margem bruta % sobre preço de venda',
  `margem_lucro_liquida` decimal(10,4) NOT NULL DEFAULT '0.0000' COMMENT 'Margem após impostos/comissões (manual)',
  `impostos_percentual` decimal(10,4) NOT NULL DEFAULT '0.0000' COMMENT 'Alíquota total de impostos sobre venda (%)',
  `moeda` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'BRL',
  `controla_estoque` tinyint(1) NOT NULL DEFAULT '1',
  `estoque_atual` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `estoque_minimo` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'Gatilho de alerta de reposição',
  `estoque_maximo` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `ponto_reposicao` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'Qtd para disparar pedido de compra',
  `lead_time_dias` int(11) NOT NULL DEFAULT '0' COMMENT 'Prazo médio de entrega do fornecedor (dias)',
  `localizacao_estoque` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Prateleira, corredor, depósito',
  `controla_validade` tinyint(1) NOT NULL DEFAULT '0',
  `alerta_validade_dias` int(11) NOT NULL DEFAULT '90' COMMENT 'Dias antes do vencimento para alertar',
  `lote_obrigatorio` tinyint(1) NOT NULL DEFAULT '0',
  `controla_depreciacao` tinyint(1) NOT NULL DEFAULT '0',
  `vida_util_meses` int(11) DEFAULT NULL COMMENT 'Vida útil em meses (ex: 60 = 5 anos)',
  `valor_residual` decimal(15,4) DEFAULT '0.0000' COMMENT 'Valor ao final da vida útil',
  `metodo_depreciacao` enum('linear','soma_digitos','unidades_produzidas') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'linear',
  `depreciacao_mensal` decimal(15,4) DEFAULT '0.0000' COMMENT 'Valor mensal calculado',
  `alerta_substituicao_meses` int(11) DEFAULT NULL COMMENT 'Meses antes do fim da vida útil para sugerir troca',
  `peso_kg` decimal(10,4) DEFAULT NULL,
  `altura_cm` decimal(10,4) DEFAULT NULL,
  `largura_cm` decimal(10,4) DEFAULT NULL,
  `profundidade_cm` decimal(10,4) DEFAULT NULL,
  `voltagem` enum('110V','220V','bivolt','DC','N/A') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `potencia_w` decimal(10,2) DEFAULT NULL,
  `garantia_meses` int(11) NOT NULL DEFAULT '0',
  `garantia_estendida_meses` int(11) NOT NULL DEFAULT '0',
  `assistencia_tecnica` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nome/contato da assistência técnica',
  `manual_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ficha_tecnica_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `palavras_chave` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Tags para busca e IA',
  `publico_alvo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ex: UTI, Laboratório, Clínica Geral',
  `indicacoes_uso` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Indicações clínicas',
  `contraindicacoes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `diferenciais` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Diferenciais competitivos',
  `concorrentes` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Produtos concorrentes (para IA de precificação)',
  `score_venda` tinyint(3) UNSIGNED DEFAULT '0' COMMENT '0-100: score de facilidade de venda (IA)',
  `ciclo_venda_dias` int(11) DEFAULT NULL COMMENT 'Ciclo médio de venda em dias',
  `taxa_conversao` decimal(5,2) DEFAULT NULL COMMENT '% de propostas que viram venda',
  `ultima_venda_em` date DEFAULT NULL,
  `total_vendido` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'Quantidade total vendida (histórico)',
  `receita_total` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT 'Receita total gerada',
  `imagem_principal` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `imagens_adicionais` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'JSON array de paths',
  `video_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `catalogo_pdf_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('ativo','inativo','descontinuado','em_homologacao') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ativo',
  `visivel_proposta` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Aparece na seleção de itens de proposta',
  `visivel_catalogo` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Aparece no catálogo público',
  `requer_instalacao` tinyint(1) NOT NULL DEFAULT '0',
  `requer_treinamento` tinyint(1) NOT NULL DEFAULT '0',
  `requer_anvisa` tinyint(1) NOT NULL DEFAULT '0',
  `observacoes_internas` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `produtos_bkp_20260604`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `produtos_bkp_deprec_20260604`
--

DROP TABLE IF EXISTS `produtos_bkp_deprec_20260604`;
CREATE TABLE `produtos_bkp_deprec_20260604` (
  `id` int(11) NOT NULL DEFAULT '0',
  `usuario_id` int(11) NOT NULL COMMENT 'Dono/tenant do registro',
  `nome` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `controla_depreciacao` tinyint(1) NOT NULL DEFAULT '0',
  `vida_util_meses` int(11) DEFAULT NULL COMMENT 'Vida útil em meses (ex: 60 = 5 anos)',
  `valor_residual` decimal(15,4) DEFAULT '0.0000' COMMENT 'Valor ao final da vida útil',
  `metodo_depreciacao` enum('linear','soma_digitos','unidades_produzidas') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'linear',
  `depreciacao_mensal` decimal(15,4) DEFAULT '0.0000' COMMENT 'Valor mensal calculado',
  `alerta_substituicao_meses` int(11) DEFAULT NULL COMMENT 'Meses antes do fim da vida útil para sugerir troca'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `produtos_bkp_deprec_20260604`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `produto_codigo_seq`
--

DROP TABLE IF EXISTS `produto_codigo_seq`;
CREATE TABLE `produto_codigo_seq` (
  `usuario_id` int(11) NOT NULL,
  `ultimo_seq` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sequência de código por tenant para geração incremental de código de produto';

--
-- Despejando dados para a tabela `produto_codigo_seq`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `produto_comissoes`
--

DROP TABLE IF EXISTS `produto_comissoes`;
CREATE TABLE `produto_comissoes` (
  `id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `colaborador_id` int(11) DEFAULT NULL COMMENT 'NULL = regra global para todos os colaboradores',
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('percentual_venda','valor_fixo','percentual_margem','percentual_lucro') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'percentual_venda',
  `valor` decimal(10,4) NOT NULL DEFAULT '0.0000',
  `meta_minima` decimal(15,4) DEFAULT NULL COMMENT 'Valor mínimo de venda para acionar a comissão',
  `meta_maxima` decimal(15,4) DEFAULT NULL COMMENT 'Valor máximo (acima disso, usa próxima faixa)',
  `escalonado` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Se 1, aplica faixas progressivas',
  `vigencia_inicio` date DEFAULT NULL,
  `vigencia_fim` date DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Regras de comissionamento por produto/serviço';

-- --------------------------------------------------------

--
-- Estrutura para tabela `produto_componentes`
--

DROP TABLE IF EXISTS `produto_componentes`;
CREATE TABLE `produto_componentes` (
  `id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL COMMENT 'Produto pai',
  `componente_id` int(11) NOT NULL COMMENT 'FK produtos.id (componente)',
  `usuario_id` int(11) NOT NULL,
  `quantidade` decimal(15,4) NOT NULL DEFAULT '1.0000',
  `obrigatorio` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Componente obrigatório na venda',
  `vendido_separado` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Pode ser vendido separadamente',
  `preco_venda_proprio` decimal(15,4) DEFAULT NULL COMMENT 'Preço quando vendido como componente (NULL = usa preco_venda do produto)',
  `desconto_composicao` decimal(10,4) NOT NULL DEFAULT '0.0000' COMMENT 'Desconto % quando vendido como parte do kit',
  `ordem` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `observacoes` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Componentes/peças que compõem um produto (BOM simplificado)';

--
-- Despejando dados para a tabela `produto_componentes`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `produto_historico_precos`
--

DROP TABLE IF EXISTS `produto_historico_precos`;
CREATE TABLE `produto_historico_precos` (
  `id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `preco_custo` decimal(15,4) NOT NULL,
  `preco_venda` decimal(15,4) NOT NULL,
  `markup_percentual` decimal(10,4) NOT NULL,
  `motivo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usuario_responsavel` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Histórico de alterações de preço do produto';

--
-- Despejando dados para a tabela `produto_historico_precos`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `produto_lotes`
--

DROP TABLE IF EXISTS `produto_lotes`;
CREATE TABLE `produto_lotes` (
  `id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `numero_lote` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `numero_serie` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_fabricacao` date DEFAULT NULL,
  `data_validade` date DEFAULT NULL,
  `quantidade_entrada` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `quantidade_atual` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `preco_custo_lote` decimal(15,4) DEFAULT NULL,
  `fornecedor_id` int(11) DEFAULT NULL,
  `nota_fiscal` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('disponivel','reservado','vencido','descartado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'disponivel',
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Controle de lotes e rastreabilidade de produtos';

-- --------------------------------------------------------

--
-- Estrutura para tabela `produto_movimentacoes`
--

DROP TABLE IF EXISTS `produto_movimentacoes`;
CREATE TABLE `produto_movimentacoes` (
  `id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo` enum('entrada_compra','entrada_devolucao','entrada_ajuste','saida_venda','saida_uso_interno','saida_perda','saida_ajuste','transferencia') COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantidade` decimal(15,4) NOT NULL,
  `saldo_anterior` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `saldo_posterior` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `preco_unitario` decimal(15,4) DEFAULT NULL,
  `lote` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_validade` date DEFAULT NULL,
  `numero_serie` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `documento_ref` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'NF, pedido, proposta etc.',
  `observacoes` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usuario_responsavel` int(11) DEFAULT NULL COMMENT 'Usuário que realizou a movimentação',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Histórico de movimentações de estoque';

-- --------------------------------------------------------

--
-- Estrutura para tabela `rdv_aprovacoes`
--

DROP TABLE IF EXISTS `rdv_aprovacoes`;
CREATE TABLE `rdv_aprovacoes` (
  `id` int(11) NOT NULL,
  `viagem_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL COMMENT 'Aprovador',
  `status` enum('pendente','aprovado','reprovado') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'pendente',
  `observacao` text COLLATE utf8_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `rdv_categorias`
--

DROP TABLE IF EXISTS `rdv_categorias`;
CREATE TABLE `rdv_categorias` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL COMMENT 'Dono da categoria (0 = sistema/global)',
  `nome` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `icone` varchar(50) COLLATE utf8_unicode_ci DEFAULT 'fa-receipt',
  `cor` varchar(20) COLLATE utf8_unicode_ci DEFAULT '#6b7280',
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `sistema` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = categoria padrão do sistema',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `rdv_categorias`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `rdv_despesas`
--

DROP TABLE IF EXISTS `rdv_despesas`;
CREATE TABLE `rdv_despesas` (
  `id` int(11) NOT NULL,
  `viagem_id` int(11) NOT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `forma_pagamento_id` int(11) DEFAULT NULL,
  `descricao` varchar(500) COLLATE utf8_unicode_ci NOT NULL,
  `valor` decimal(12,2) NOT NULL DEFAULT '0.00',
  `data_documento` date DEFAULT NULL,
  `numero_documento` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `fornecedor` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cnpj_fornecedor` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cidade` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `hora_documento` time DEFAULT NULL,
  `arquivo` varchar(500) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Caminho do comprovante',
  `ocr_json` text COLLATE utf8_unicode_ci COMMENT 'JSON retornado pelo OCR',
  `ocr_status` enum('pendente','processando','concluido','erro') COLLATE utf8_unicode_ci DEFAULT NULL,
  `fora_periodo` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = despesa fora do período autorizado',
  `log_fora_periodo` text COLLATE utf8_unicode_ci,
  `tipo` enum('simples','completa') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'simples',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `rdv_formas_pagamento`
--

DROP TABLE IF EXISTS `rdv_formas_pagamento`;
CREATE TABLE `rdv_formas_pagamento` (
  `id` int(11) NOT NULL,
  `nome` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `sistema` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `rdv_formas_pagamento`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `rdv_historico`
--

DROP TABLE IF EXISTS `rdv_historico`;
CREATE TABLE `rdv_historico` (
  `id` int(11) NOT NULL,
  `viagem_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `usuario_nome` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `tipo` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT 'status_change, despesa_add, aprovacao, etc.',
  `descricao` text COLLATE utf8_unicode_ci NOT NULL,
  `dados_extras` text COLLATE utf8_unicode_ci COMMENT 'JSON com dados adicionais',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `rdv_historico`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `rdv_ocr_logs`
--

DROP TABLE IF EXISTS `rdv_ocr_logs`;
CREATE TABLE `rdv_ocr_logs` (
  `id` int(11) NOT NULL,
  `viagem_id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `arquivo` varchar(500) COLLATE utf8_unicode_ci DEFAULT NULL,
  `engine` varchar(40) COLLATE utf8_unicode_ci NOT NULL COMMENT 'tesseract.js, ocrspace, openai',
  `sucesso` tinyint(1) NOT NULL DEFAULT '0',
  `confianca` decimal(5,2) DEFAULT NULL COMMENT 'percentual 0-100',
  `tempo_ms` int(11) DEFAULT NULL,
  `erro` text COLLATE utf8_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `rdv_ocr_logs`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `rdv_rotas`
--

DROP TABLE IF EXISTS `rdv_rotas`;
CREATE TABLE `rdv_rotas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL COMMENT 'Criador da rota',
  `nome` varchar(200) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Ex: Triângulo Mineiro, Sul de MG',
  `descricao` text COLLATE utf8_unicode_ci,
  `tipo` enum('padrao','livre') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'padrao' COMMENT 'padrao = com clientes; livre = sem controle',
  `regiao` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Região geográfica',
  `estado` char(2) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `rdv_rotas`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `rdv_rota_clientes`
--

DROP TABLE IF EXISTS `rdv_rota_clientes`;
CREATE TABLE `rdv_rota_clientes` (
  `id` int(11) NOT NULL,
  `rota_id` int(11) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL COMMENT 'FK clientes.id (pode ser null se for lead)',
  `lead_id` int(11) DEFAULT NULL COMMENT 'FK crm_leads.id (pode ser null se for cliente)',
  `oportunidade_id` int(11) DEFAULT NULL COMMENT 'FK crm_oportunidades.id (opcional)',
  `ordem` int(11) NOT NULL DEFAULT '0' COMMENT 'Ordem de visita sugerida',
  `observacoes` varchar(500) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `rdv_seq`
--

DROP TABLE IF EXISTS `rdv_seq`;
CREATE TABLE `rdv_seq` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `ano` int(4) NOT NULL,
  `ultimo_numero` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `rdv_seq`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `rdv_viagens`
--

DROP TABLE IF EXISTS `rdv_viagens`;
CREATE TABLE `rdv_viagens` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL COMMENT 'Vendedor responsável',
  `rota_id` int(11) DEFAULT NULL COMMENT 'Rota comercial associada (pode ser null)',
  `codigo` varchar(30) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Ex: RDV-2026-00001',
  `nome` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `status` enum('aberto','iniciado','concluido','cancelado') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'aberto',
  `periodo_inicio` date NOT NULL,
  `periodo_fim` date NOT NULL,
  `motivo` text COLLATE utf8_unicode_ci,
  `cidade` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `estado` char(2) COLLATE utf8_unicode_ci DEFAULT NULL,
  `pais` varchar(60) COLLATE utf8_unicode_ci DEFAULT 'Brasil',
  `valor_previsto` decimal(12,2) NOT NULL DEFAULT '0.00',
  `valor_real` decimal(12,2) NOT NULL DEFAULT '0.00',
  `observacoes` text COLLATE utf8_unicode_ci,
  `aprovacao_status` enum('pendente','aprovado','reprovado') COLLATE utf8_unicode_ci DEFAULT NULL,
  `aprovado_por` int(11) DEFAULT NULL,
  `aprovado_em` datetime DEFAULT NULL,
  `conta_pagar_id` int(11) DEFAULT NULL COMMENT 'FK contas_pagar.id após integração financeira',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `rdv_viagens`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `security_two_factor_logs`
--

DROP TABLE IF EXISTS `security_two_factor_logs`;
CREATE TABLE `security_two_factor_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `os` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `browser` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action` enum('code_sent','verify_success','verify_failed','resend','locked','enabled','disabled') COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `security_two_factor_logs`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `tabela_exames`
--

DROP TABLE IF EXISTS `tabela_exames`;
CREATE TABLE `tabela_exames` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nome_exame` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `modalidade` enum('TC','RM','RX','US') COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor_padrao` decimal(10,2) NOT NULL DEFAULT '0.00',
  `nivel` int(11) DEFAULT NULL,
  `perc_rotina` decimal(5,2) NOT NULL DEFAULT '0.00',
  `perc_urgencia` decimal(5,2) NOT NULL DEFAULT '0.00',
  `valor_rotina` decimal(10,2) NOT NULL DEFAULT '0.00',
  `valor_urgencia` decimal(10,2) NOT NULL DEFAULT '0.00',
  `imposto_icms` decimal(5,2) NOT NULL DEFAULT '0.00',
  `imposto_ipi` decimal(5,2) NOT NULL DEFAULT '0.00',
  `imposto_pis_cofins` decimal(5,2) NOT NULL DEFAULT '0.00',
  `imposto_simples` decimal(5,2) NOT NULL DEFAULT '0.00',
  `custo_comissao` decimal(5,2) NOT NULL DEFAULT '0.00',
  `custo_mao_obra_direta` decimal(5,2) NOT NULL DEFAULT '0.00',
  `custo_mao_obra_indireta` decimal(5,2) NOT NULL DEFAULT '0.00',
  `margem_lucro` decimal(5,2) NOT NULL DEFAULT '0.00',
  `perc_venda_rotina` decimal(8,4) NOT NULL DEFAULT '0.0000' COMMENT 'Margem % venda rotina',
  `perc_venda_urgencia` decimal(8,4) NOT NULL DEFAULT '0.0000' COMMENT 'Margem % venda urgência',
  `valor_venda_rotina` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Valor final venda rotina',
  `valor_venda_urgencia` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Valor final venda urgência',
  `preco_custo` decimal(10,2) NOT NULL DEFAULT '0.00',
  `preco_venda` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `tabela_exames`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `tabela_exames_tags`
--

DROP TABLE IF EXISTS `tabela_exames_tags`;
CREATE TABLE `tabela_exames_tags` (
  `id` int(10) UNSIGNED NOT NULL,
  `exame_id` int(10) UNSIGNED NOT NULL,
  `tag_nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tag_valor` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `tabela_exames_tags`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'user',
  `status` enum('ativo','inativo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ativo',
  `two_factor_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `two_factor_code` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `two_factor_expiration` datetime DEFAULT NULL,
  `two_factor_attempts` int(11) NOT NULL DEFAULT '0',
  `two_factor_last_sent` datetime DEFAULT NULL,
  `two_factor_validated` tinyint(1) NOT NULL DEFAULT '0',
  `two_factor_locked_until` datetime DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `users`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `whatsapp_bot_logs`
--

DROP TABLE IF EXISTS `whatsapp_bot_logs`;
CREATE TABLE `whatsapp_bot_logs` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL COMMENT 'ID do usuário do ERP (tenant)',
  `integracao_id` int(11) NOT NULL DEFAULT '0' COMMENT 'ID da integração na tabela integracoes',
  `telefone_hash` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Hash SHA-256 truncado do telefone (privacidade)',
  `endpoint` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Endpoint consultado',
  `intent` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Intenção identificada',
  `status` enum('success','error') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'success',
  `summary` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Resumo da resposta',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Logs de consultas do chatbot WhatsApp';

--
-- Despejando dados para a tabela `whatsapp_bot_logs`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `_cnes_equip_staging`
--

DROP TABLE IF EXISTS `_cnes_equip_staging`;
CREATE TABLE `_cnes_equip_staging` (
  `co_unidade` varchar(30) DEFAULT NULL,
  `co_equipamento` varchar(4) DEFAULT NULL,
  `co_tipo_equip` varchar(4) DEFAULT NULL,
  `qt_existente` int(11) DEFAULT NULL,
  `qt_uso` int(11) DEFAULT NULL,
  `tp_sus` varchar(1) DEFAULT NULL,
  `qt_sus` int(11) DEFAULT NULL,
  `dt_atualizacao` varchar(10) DEFAULT NULL,
  `co_usuario` varchar(50) DEFAULT NULL,
  `dt_atu_origem` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estrutura para tabela `_cnes_estab_staging`
--

DROP TABLE IF EXISTS `_cnes_estab_staging`;
CREATE TABLE `_cnes_estab_staging` (
  `co_unidade` varchar(30) DEFAULT NULL,
  `co_cnes` varchar(7) DEFAULT NULL,
  `nu_cnpj_manten` varchar(14) DEFAULT NULL,
  `tp_pfpj` varchar(1) DEFAULT NULL,
  `nivel_dep` varchar(1) DEFAULT NULL,
  `no_razao_social` varchar(255) DEFAULT NULL,
  `no_fantasia` varchar(255) DEFAULT NULL,
  `no_logradouro` varchar(255) DEFAULT NULL,
  `nu_endereco` varchar(10) DEFAULT NULL,
  `no_complemento` varchar(100) DEFAULT NULL,
  `no_bairro` varchar(100) DEFAULT NULL,
  `co_cep` varchar(8) DEFAULT NULL,
  `co_regiao_saude` varchar(4) DEFAULT NULL,
  `co_micro_regiao` varchar(6) DEFAULT NULL,
  `co_distrito_san` varchar(4) DEFAULT NULL,
  `co_distrito_adm` varchar(4) DEFAULT NULL,
  `nu_telefone` varchar(20) DEFAULT NULL,
  `nu_fax` varchar(20) DEFAULT NULL,
  `no_email` varchar(100) DEFAULT NULL,
  `nu_cpf` varchar(11) DEFAULT NULL,
  `nu_cnpj` varchar(14) DEFAULT NULL,
  `co_atividade` varchar(2) DEFAULT NULL,
  `co_clientela` varchar(2) DEFAULT NULL,
  `nu_alvara` varchar(20) DEFAULT NULL,
  `dt_expedicao` varchar(10) DEFAULT NULL,
  `tp_orgao_exp` varchar(2) DEFAULT NULL,
  `dt_val_lic_sani` varchar(10) DEFAULT NULL,
  `tp_lic_sani` varchar(1) DEFAULT NULL,
  `tp_unidade` varchar(2) DEFAULT NULL,
  `co_turno_atend` varchar(2) DEFAULT NULL,
  `co_estado_gestor` varchar(2) DEFAULT NULL,
  `co_municipio_ges` varchar(6) DEFAULT NULL,
  `dt_atualizacao` varchar(10) DEFAULT NULL,
  `co_usuario` varchar(50) DEFAULT NULL,
  `co_cpf_diretor` varchar(11) DEFAULT NULL,
  `reg_diretor` varchar(20) DEFAULT NULL,
  `st_adesao_filant` varchar(1) DEFAULT NULL,
  `co_motivo_desab` varchar(2) DEFAULT NULL,
  `no_url` varchar(255) DEFAULT NULL,
  `nu_latitude` varchar(20) DEFAULT NULL,
  `nu_longitude` varchar(20) DEFAULT NULL,
  `dt_atu_geo` varchar(10) DEFAULT NULL,
  `no_usuario_geo` varchar(50) DEFAULT NULL,
  `co_natureza_jur` varchar(4) DEFAULT NULL,
  `tp_sempre_aberto` varchar(1) DEFAULT NULL,
  `st_gera_credito` varchar(1) DEFAULT NULL,
  `st_conexao_int` varchar(1) DEFAULT NULL,
  `co_tipo_unidade` varchar(4) DEFAULT NULL,
  `no_fantasia_abrev` varchar(30) DEFAULT NULL,
  `tp_gestao` varchar(1) DEFAULT NULL,
  `dt_atu_origem` varchar(10) DEFAULT NULL,
  `co_tipo_estab` varchar(2) DEFAULT NULL,
  `co_ativ_principal` varchar(2) DEFAULT NULL,
  `st_contrato_form` varchar(1) DEFAULT NULL,
  `co_tipo_abrang` varchar(1) DEFAULT NULL,
  `st_coworking` varchar(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estrutura para tabela `_cnes_prof_staging`
--

DROP TABLE IF EXISTS `_cnes_prof_staging`;
CREATE TABLE `_cnes_prof_staging` (
  `co_profissional` varchar(32) DEFAULT NULL,
  `co_cpf` varchar(14) DEFAULT NULL,
  `no_profissional` varchar(255) DEFAULT NULL,
  `co_cns` varchar(20) DEFAULT NULL,
  `dt_atualizacao` varchar(10) DEFAULT NULL,
  `co_usuario` varchar(50) DEFAULT NULL,
  `st_nmprof` varchar(1) DEFAULT NULL,
  `co_nacionalidade` varchar(3) DEFAULT NULL,
  `co_seq_inclusao` varchar(10) DEFAULT NULL,
  `dt_atu_origem` varchar(10) DEFAULT NULL,
  `no_social` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estrutura para tabela `_cnes_vinculo_staging`
--

DROP TABLE IF EXISTS `_cnes_vinculo_staging`;
CREATE TABLE `_cnes_vinculo_staging` (
  `co_unidade` varchar(30) DEFAULT NULL,
  `co_profissional` varchar(32) DEFAULT NULL,
  `co_cbo` varchar(6) DEFAULT NULL,
  `tp_sus_nao_sus` varchar(1) DEFAULT NULL,
  `ind_vinculacao` varchar(6) DEFAULT NULL,
  `tp_terceiro_sih` varchar(1) DEFAULT NULL,
  `qt_ch_ambulat` int(11) DEFAULT NULL,
  `co_conselho` varchar(2) DEFAULT NULL,
  `nu_registro` varchar(20) DEFAULT NULL,
  `sg_uf_crm` varchar(2) DEFAULT NULL,
  `tp_preceptor` varchar(1) DEFAULT NULL,
  `tp_residente` varchar(1) DEFAULT NULL,
  `nu_cnpj_detalhe` varchar(14) DEFAULT NULL,
  `dt_atualizacao` varchar(10) DEFAULT NULL,
  `co_usuario` varchar(50) DEFAULT NULL,
  `dt_atu_origem` varchar(10) DEFAULT NULL,
  `qt_ch_outros` int(11) DEFAULT NULL,
  `qt_ch_hosp_sus` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `apuracao_itens`
--
ALTER TABLE `apuracao_itens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_apuracao_itens_apuracao` (`apuracao_id`),
  ADD KEY `idx_apuracao_itens_modalidade` (`modalidade`);

--
-- Índices de tabela `apuracoes`
--
ALTER TABLE `apuracoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_apuracoes_usuario` (`usuario_id`),
  ADD KEY `idx_apuracoes_contrato` (`contrato_id`),
  ADD KEY `idx_apuracoes_medico` (`medico_id`),
  ADD KEY `idx_apuracoes_status` (`status`),
  ADD KEY `idx_apuracao_mae_id` (`apuracao_mae_id`);

--
-- Índices de tabela `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Índices de tabela `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cpf_cnpj` (`cpf_cnpj`),
  ADD KEY `idx_cpf_cnpj` (`cpf_cnpj`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_tipo` (`tipo`),
  ADD KEY `idx_usuario_id` (`usuario_id`),
  ADD KEY `idx_clientes_data_cadastro` (`data_cadastro`),
  ADD KEY `idx_clientes_razao_social` (`razao_social`),
  ADD KEY `idx_clientes_created_at` (`created_at`),
  ADD KEY `idx_clientes_crm_lead` (`crm_lead_id`);

--
-- Índices de tabela `clientes_anexos`
--
ALTER TABLE `clientes_anexos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `clientes_contatos`
--
ALTER TABLE `clientes_contatos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cliente_id` (`cliente_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_clientes_contatos_cliente_status` (`cliente_id`,`status`);

--
-- Índices de tabela `cnes_dom_cbo`
--
ALTER TABLE `cnes_dom_cbo`
  ADD PRIMARY KEY (`co_cbo`);

--
-- Índices de tabela `cnes_dom_conselho`
--
ALTER TABLE `cnes_dom_conselho`
  ADD PRIMARY KEY (`co_conselho`);

--
-- Índices de tabela `cnes_dom_equipamentos`
--
ALTER TABLE `cnes_dom_equipamentos`
  ADD PRIMARY KEY (`co_equipamento`);

--
-- Índices de tabela `cnes_dom_tipo_equipamento`
--
ALTER TABLE `cnes_dom_tipo_equipamento`
  ADD PRIMARY KEY (`co_tipo`);

--
-- Índices de tabela `cnes_equipamentos`
--
ALTER TABLE `cnes_equipamentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_unidade_equip` (`co_unidade`,`co_equipamento`),
  ADD KEY `idx_co_unidade_equip` (`co_unidade`),
  ADD KEY `idx_co_tipo` (`co_tipo_equipamento`),
  ADD KEY `idx_co_equipamento` (`co_equipamento`),
  ADD KEY `idx_co_cnes` (`co_cnes`),
  ADD KEY `idx_unidade_cnes` (`co_unidade`,`co_cnes`);

--
-- Índices de tabela `cnes_estabelecimentos`
--
ALTER TABLE `cnes_estabelecimentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_co_cnes` (`co_cnes`),
  ADD KEY `idx_co_unidade` (`co_unidade`),
  ADD KEY `idx_co_estado` (`co_estado_gestor`),
  ADD KEY `idx_co_municipio` (`co_municipio_gestor`),
  ADD KEY `idx_no_razao` (`no_razao_social`(100)),
  ADD KEY `idx_nu_cnpj` (`nu_cnpj`),
  ADD KEY `idx_cliente_id` (`cliente_id`),
  ADD KEY `idx_competencia` (`competencia`);

--
-- Índices de tabela `cnes_importacoes`
--
ALTER TABLE `cnes_importacoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_competencia` (`competencia`);

--
-- Índices de tabela `cnes_profissionais`
--
ALTER TABLE `cnes_profissionais`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_co_unidade_prof` (`co_unidade`),
  ADD KEY `idx_co_cbo` (`co_cbo`),
  ADD KEY `idx_no_profissional` (`no_profissional`(100)),
  ADD KEY `idx_situacao` (`situacao`),
  ADD KEY `idx_co_cnes` (`co_cnes`),
  ADD KEY `idx_unidade_cnes` (`co_unidade`,`co_cnes`);

--
-- Índices de tabela `colaboradores`
--
ALTER TABLE `colaboradores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_colaboradores_usuario` (`usuario_id`),
  ADD KEY `idx_colaboradores_cpf_cnpj` (`cpf_cnpj`),
  ADD KEY `idx_colaboradores_status` (`status`),
  ADD KEY `idx_colaboradores_tipo` (`tipo_contratacao`),
  ADD KEY `idx_colaboradores_user_id` (`user_id`);

--
-- Índices de tabela `colaboradores_anexos`
--
ALTER TABLE `colaboradores_anexos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_col_anexos_colaborador` (`colaborador_id`),
  ADD KEY `idx_col_anexos_usuario` (`usuario_id`);

--
-- Índices de tabela `colaboradores_comissoes`
--
ALTER TABLE `colaboradores_comissoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_col_comissoes_colaborador` (`colaborador_id`),
  ADD KEY `idx_col_comissoes_usuario` (`usuario_id`);

--
-- Índices de tabela `configuracoes_financeiras`
--
ALTER TABLE `configuracoes_financeiras`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_usuario_id` (`usuario_id`);

--
-- Índices de tabela `config_nfs`
--
ALTER TABLE `config_nfs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_config_nfs_usuario` (`usuario_id`),
  ADD KEY `idx_config_nfs_usuario` (`usuario_id`);

--
-- Índices de tabela `contas_bancarias`
--
ALTER TABLE `contas_bancarias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_ativa` (`ativa`),
  ADD KEY `idx_openfinance_item` (`openfinance_item_id`),
  ADD KEY `idx_openfinance_account` (`openfinance_account_id`);

--
-- Índices de tabela `contas_movimentacoes`
--
ALTER TABLE `contas_movimentacoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_origem_hash` (`conta_bancaria_id`,`origem_hash`),
  ADD KEY `idx_conta_bancaria` (`conta_bancaria_id`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_data` (`data_movimentacao`),
  ADD KEY `idx_tipo` (`tipo`),
  ADD KEY `idx_origem` (`origem`),
  ADD KEY `idx_origem_hash` (`origem_hash`),
  ADD KEY `idx_openfinance_tx` (`openfinance_tx_id`),
  ADD KEY `idx_conta_pagar` (`conta_pagar_id`),
  ADD KEY `idx_conta_receber` (`conta_receber_id`),
  ADD KEY `idx_data_tipo` (`conta_bancaria_id`,`data_movimentacao`,`tipo`);

--
-- Índices de tabela `contas_pagar`
--
ALTER TABLE `contas_pagar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contas_pagar_usuario` (`usuario_id`),
  ADD KEY `idx_contas_pagar_plano_conta` (`plano_conta_id`),
  ADD KEY `idx_contas_pagar_fornecedor` (`fornecedor_id`),
  ADD KEY `idx_contas_pagar_vencimento` (`data_vencimento`);

--
-- Índices de tabela `contas_pagar_anexos`
--
ALTER TABLE `contas_pagar_anexos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contas_pagar_anexos_usuario` (`usuario_id`),
  ADD KEY `idx_contas_pagar_anexos_conta` (`conta_pagar_id`);

--
-- Índices de tabela `contas_receber`
--
ALTER TABLE `contas_receber`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contas_receber_usuario` (`usuario_id`),
  ADD KEY `idx_contas_receber_cliente` (`cliente_id`),
  ADD KEY `idx_contas_receber_plano_conta` (`plano_conta_id`),
  ADD KEY `idx_contas_receber_vencimento` (`data_vencimento`),
  ADD KEY `idx_contas_receber_asaas_payment` (`asaas_payment_id`),
  ADD KEY `idx_contas_receber_external_ref` (`external_reference`),
  ADD KEY `idx_cr_grupo_parcelas` (`grupo_parcelas`),
  ADD KEY `idx_cr_contrato_id` (`contrato_id`),
  ADD KEY `idx_contas_receber_cora_invoice_id` (`cora_invoice_id`),
  ADD KEY `idx_cr_colaborador` (`colaborador_id`),
  ADD KEY `idx_cr_nf_avulsa` (`emitir_nf_avulsa`,`nf_avulsa_status`);

--
-- Índices de tabela `contas_receber_anexos`
--
ALTER TABLE `contas_receber_anexos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `contratos`
--
ALTER TABLE `contratos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contratos_usuario` (`usuario_id`),
  ADD KEY `idx_contratos_medico` (`medico_id`),
  ADD KEY `idx_contratos_cliente` (`cliente_id`),
  ADD KEY `idx_contratos_status` (`status`),
  ADD KEY `idx_contratos_plano_conta` (`plano_conta_id`);

--
-- Índices de tabela `contratos_anexos`
--
ALTER TABLE `contratos_anexos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contratos_anexos_contrato` (`contrato_id`);

--
-- Índices de tabela `contrato_exames`
--
ALTER TABLE `contrato_exames`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_contrato_exame` (`contrato_id`,`tabela_exame_id`),
  ADD KEY `idx_contrato_id` (`contrato_id`),
  ADD KEY `idx_tabela_exame_id` (`tabela_exame_id`),
  ADD KEY `idx_usuario_id` (`usuario_id`);

--
-- Índices de tabela `contrato_modalidades`
--
ALTER TABLE `contrato_modalidades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contrato_modalidades_contrato` (`contrato_id`);

--
-- Índices de tabela `crm_anexos`
--
ALTER TABLE `crm_anexos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_related` (`related_type`,`related_id`),
  ADD KEY `idx_usuario_id` (`usuario_id`),
  ADD KEY `idx_tipo` (`tipo_documento`);

--
-- Índices de tabela `crm_interacoes`
--
ALTER TABLE `crm_interacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_crm_int_related` (`related_type`,`related_id`),
  ADD KEY `idx_crm_int_usuario` (`usuario_id`),
  ADD KEY `idx_crm_int_data` (`data_interacao`),
  ADD KEY `idx_int_relatorio` (`usuario_id`,`tipo_interacao`,`data_interacao`),
  ADD KEY `idx_int_related` (`related_type`,`related_id`);

--
-- Índices de tabela `crm_leads`
--
ALTER TABLE `crm_leads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_crm_lead_usuario` (`usuario_id`),
  ADD KEY `idx_crm_lead_status` (`status_lead`),
  ADD KEY `idx_crm_lead_segmento` (`segmento_principal`),
  ADD KEY `idx_crm_lead_proximo` (`data_proximo_contato`),
  ADD KEY `idx_leads_relatorio` (`usuario_id`,`status_lead`,`created_at`),
  ADD KEY `idx_leads_origem` (`origem`),
  ADD KEY `idx_leads_segmento` (`segmento_principal`),
  ADD KEY `idx_leads_proximo_contato` (`data_proximo_contato`);

--
-- Índices de tabela `crm_oportunidades`
--
ALTER TABLE `crm_oportunidades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_crm_op_usuario` (`usuario_id`),
  ADD KEY `idx_crm_op_lead` (`lead_id`),
  ADD KEY `idx_crm_op_cliente` (`cliente_id`),
  ADD KEY `idx_crm_op_etapa` (`etapa_funil`),
  ADD KEY `idx_crm_op_status` (`status_oportunidade`),
  ADD KEY `idx_ops_relatorio` (`usuario_id`,`status_oportunidade`,`created_at`),
  ADD KEY `idx_ops_etapa` (`etapa_funil`),
  ADD KEY `idx_ops_tipo_contrato` (`tipo_contrato`);

--
-- Índices de tabela `crm_oportunidade_modalidades`
--
ALTER TABLE `crm_oportunidade_modalidades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_crm_op_mod_op` (`oportunidade_id`);

--
-- Índices de tabela `crm_propostas`
--
ALTER TABLE `crm_propostas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_prop_token` (`token_acesso`),
  ADD KEY `idx_prop_usuario` (`usuario_id`),
  ADD KEY `idx_prop_oportunidade` (`oportunidade_id`),
  ADD KEY `idx_prop_cliente` (`cliente_id`),
  ADD KEY `idx_prop_status` (`status`),
  ADD KEY `idx_prop_numero` (`numero`),
  ADD KEY `idx_prop_token` (`token_acesso`);

--
-- Índices de tabela `crm_proposta_aceite`
--
ALTER TABLE `crm_proposta_aceite`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_aceite_proposta` (`proposta_id`),
  ADD KEY `idx_aceite_evento` (`evento`);

--
-- Índices de tabela `crm_proposta_historico`
--
ALTER TABLE `crm_proposta_historico`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hist_proposta` (`proposta_id`);

--
-- Índices de tabela `crm_proposta_itens`
--
ALTER TABLE `crm_proposta_itens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_item_proposta` (`proposta_id`);

--
-- Índices de tabela `crm_transferencias`
--
ALTER TABLE `crm_transferencias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_crm_transf_related` (`related_type`,`related_id`),
  ADD KEY `idx_crm_transf_usuario` (`usuario_id`),
  ADD KEY `idx_crm_transf_de` (`de_usuario_id`),
  ADD KEY `idx_crm_transf_para` (`para_usuario_id`);

--
-- Índices de tabela `dispositivos_controlid`
--
ALTER TABLE `dispositivos_controlid`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_ip_porta` (`ip_address`,`porta`);

--
-- Índices de tabela `dispositivos_controlid_leituras`
--
ALTER TABLE `dispositivos_controlid_leituras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dispositivo_id` (`dispositivo_id`),
  ADD KEY `idx_tag_value` (`tag_value`),
  ADD KEY `idx_data_hora` (`data_hora`);

--
-- Índices de tabela `dispositivos_controlid_sync_log`
--
ALTER TABLE `dispositivos_controlid_sync_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dispositivo_id` (`dispositivo_id`),
  ADD KEY `idx_data_hora` (`data_hora`);

--
-- Índices de tabela `email_alertas`
--
ALTER TABLE `email_alertas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_alerta_usuario_codigo` (`usuario_id`,`codigo`),
  ADD KEY `idx_alerta_modulo` (`modulo`),
  ADD KEY `idx_alerta_ativo` (`ativo`),
  ADD KEY `idx_alerta_usuario` (`usuario_id`);

--
-- Índices de tabela `email_alertas_log`
--
ALTER TABLE `email_alertas_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_log_alerta` (`alerta_id`),
  ADD KEY `idx_log_usuario` (`usuario_id`),
  ADD KEY `idx_log_data` (`disparado_em`);

--
-- Índices de tabela `empresa_config`
--
ALTER TABLE `empresa_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_empresa_usuario` (`usuario_id`);

--
-- Índices de tabela `equipamentos_cliente`
--
ALTER TABLE `equipamentos_cliente`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_equip_usuario` (`usuario_id`),
  ADD KEY `idx_equip_cliente` (`cliente_id`),
  ADD KEY `idx_equip_produto` (`produto_id`),
  ADD KEY `idx_equip_serie` (`numero_serie`(50));

--
-- Índices de tabela `especialidades`
--
ALTER TABLE `especialidades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_especialidades_usuario` (`usuario_id`),
  ADD KEY `idx_especialidades_nome` (`especialidade`);

--
-- Índices de tabela `est_movimentacoes`
--
ALTER TABLE `est_movimentacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mov_produto` (`produto_id`),
  ADD KEY `idx_mov_usuario` (`usuario_id`),
  ADD KEY `idx_mov_tipo` (`tipo`),
  ADD KEY `idx_mov_origem` (`origem`),
  ADD KEY `idx_mov_nfe_chave` (`nfe_chave`),
  ADD KEY `idx_mov_created` (`created_at`);

--
-- Índices de tabela `est_pedidos_compra`
--
ALTER TABLE `est_pedidos_compra`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_pc_numero_usuario` (`numero`,`usuario_id`),
  ADD KEY `idx_pc_usuario` (`usuario_id`),
  ADD KEY `idx_pc_fornecedor` (`fornecedor_id`),
  ADD KEY `idx_pc_status` (`status`),
  ADD KEY `idx_pc_data` (`data_pedido`);

--
-- Índices de tabela `est_pedidos_compra_itens`
--
ALTER TABLE `est_pedidos_compra_itens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pci_pedido` (`pedido_id`),
  ADD KEY `idx_pci_produto` (`produto_id`);

--
-- Índices de tabela `est_pedidos_venda`
--
ALTER TABLE `est_pedidos_venda`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_pv_numero_usuario` (`numero`,`usuario_id`),
  ADD KEY `idx_pv_usuario` (`usuario_id`),
  ADD KEY `idx_pv_cliente` (`cliente_id`),
  ADD KEY `idx_pv_status` (`status`),
  ADD KEY `idx_pv_proposta` (`proposta_id`),
  ADD KEY `idx_pv_data` (`data_pedido`);

--
-- Índices de tabela `est_pedidos_venda_itens`
--
ALTER TABLE `est_pedidos_venda_itens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pvi_pedido` (`pedido_id`),
  ADD KEY `idx_pvi_produto` (`produto_id`);

--
-- Índices de tabela `est_pedido_seq`
--
ALTER TABLE `est_pedido_seq`
  ADD PRIMARY KEY (`usuario_id`,`tipo`);

--
-- Índices de tabela `fornecedores`
--
ALTER TABLE `fornecedores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fornecedores_usuario` (`usuario_id`),
  ADD KEY `idx_fornecedores_cidade` (`cidade`),
  ADD KEY `idx_fornecedores_estado` (`estado`),
  ADD KEY `idx_fornecedores_documento` (`documento`);

--
-- Índices de tabela `historico_importacoes_ofx`
--
ALTER TABLE `historico_importacoes_ofx`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_conta_data` (`conta_id`,`importado_em`),
  ADD KEY `idx_ultimo_fitid` (`conta_id`,`ultimo_fitid`);

--
-- Índices de tabela `hub_ia_agentes`
--
ALTER TABLE `hub_ia_agentes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hubia_agente_usuario` (`usuario_id`),
  ADD KEY `idx_hubia_agente_conector` (`conector_id`);

--
-- Índices de tabela `hub_ia_agente_permissoes`
--
ALTER TABLE `hub_ia_agente_permissoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_hubia_agperm` (`agente_id`,`modulo`),
  ADD KEY `idx_hubia_agperm_agente` (`agente_id`);

--
-- Índices de tabela `hub_ia_banco_config`
--
ALTER TABLE `hub_ia_banco_config`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `hub_ia_conectores`
--
ALTER TABLE `hub_ia_conectores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hubia_conect_usuario` (`usuario_id`),
  ADD KEY `idx_hubia_conect_provider` (`provider`);

--
-- Índices de tabela `hub_ia_conhecimento_chunks`
--
ALTER TABLE `hub_ia_conhecimento_chunks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hubia_chunk_doc` (`documento_id`);

--
-- Índices de tabela `hub_ia_conhecimento_documentos`
--
ALTER TABLE `hub_ia_conhecimento_documentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hubia_doc_usuario` (`usuario_id`);

--
-- Índices de tabela `hub_ia_historico`
--
ALTER TABLE `hub_ia_historico`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hubia_hist_agente` (`agente_id`),
  ADD KEY `idx_hubia_hist_usuario` (`usuario_id`),
  ADD KEY `idx_hubia_hist_created` (`created_at`),
  ADD KEY `idx_hubia_hist_provider` (`provider`);

--
-- Índices de tabela `hub_ia_logs`
--
ALTER TABLE `hub_ia_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hubia_logs_conector` (`conector_id`),
  ADD KEY `idx_hubia_logs_created` (`created_at`);

--
-- Índices de tabela `hub_ia_prompts`
--
ALTER TABLE `hub_ia_prompts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hubia_prompt_usuario` (`usuario_id`);

--
-- Índices de tabela `hub_ia_whatsapp_config`
--
ALTER TABLE `hub_ia_whatsapp_config`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `integracoes`
--
ALTER TABLE `integracoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_integracoes_usuario` (`usuario_id`),
  ADD KEY `idx_integracoes_tipo` (`tipo`);

--
-- Índices de tabela `integracoes_logs`
--
ALTER TABLE `integracoes_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_integracoes_logs_usuario` (`usuario_id`),
  ADD KEY `idx_integracoes_logs_integracao` (`integracao_id`);

--
-- Índices de tabela `layout_exames`
--
ALTER TABLE `layout_exames`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_layout_exames_usuario` (`usuario_id`);

--
-- Índices de tabela `manual_artigos`
--
ALTER TABLE `manual_artigos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_manual_art_slug` (`slug`),
  ADD KEY `idx_manual_art_cat` (`categoria_id`),
  ADD KEY `idx_manual_art_pub` (`publicado`);

--
-- Índices de tabela `manual_categorias`
--
ALTER TABLE `manual_categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_manual_cat_slug` (`slug`);

--
-- Índices de tabela `manual_historico`
--
ALTER TABLE `manual_historico`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_manual_hist_artigo` (`artigo_id`);

--
-- Índices de tabela `manut_ordens_servico`
--
ALTER TABLE `manut_ordens_servico`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_os_usuario` (`usuario_id`),
  ADD KEY `idx_os_numero` (`numero`),
  ADD KEY `idx_os_status` (`status`),
  ADD KEY `idx_os_cliente` (`cliente_id`),
  ADD KEY `idx_os_equipamento` (`equipamento_id`),
  ADD KEY `idx_os_proposta` (`proposta_id`),
  ADD KEY `idx_os_pedido_venda` (`pedido_venda_id`);

--
-- Índices de tabela `manut_os_historico`
--
ALTER TABLE `manut_os_historico`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_oshist_os` (`os_id`),
  ADD KEY `idx_oshist_usuario` (`usuario_id`);

--
-- Índices de tabela `manut_os_seq`
--
ALTER TABLE `manut_os_seq`
  ADD PRIMARY KEY (`usuario_id`,`ano`);

--
-- Índices de tabela `manut_os_trocas`
--
ALTER TABLE `manut_os_trocas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_trocas_os` (`os_id`),
  ADD KEY `idx_trocas_produto` (`produto_id`);

--
-- Índices de tabela `marketing_campanhas`
--
ALTER TABLE `marketing_campanhas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mkt_camp_usuario` (`usuario_id`),
  ADD KEY `idx_mkt_camp_canal` (`canal`),
  ADD KEY `idx_mkt_camp_status` (`status`);

--
-- Índices de tabela `marketing_disparadores`
--
ALTER TABLE `marketing_disparadores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mkt_disp_usuario` (`usuario_id`),
  ADD KEY `idx_mkt_disp_campanha` (`campanha_id`),
  ADD KEY `idx_mkt_disp_status` (`status`),
  ADD KEY `idx_mkt_disp_agendado` (`agendado_para`);

--
-- Índices de tabela `marketing_envios`
--
ALTER TABLE `marketing_envios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mkt_env_disparador` (`disparador_id`),
  ADD KEY `idx_mkt_env_usuario` (`usuario_id`),
  ADD KEY `idx_mkt_env_dest_tipo_id` (`destinatario_tipo`,`destinatario_id`),
  ADD KEY `idx_mkt_env_status` (`status`),
  ADD KEY `idx_mkt_env_token` (`tracking_token`);

--
-- Índices de tabela `marketing_interacoes_crm`
--
ALTER TABLE `marketing_interacoes_crm`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mkt_int_related` (`related_type`,`related_id`),
  ADD KEY `idx_mkt_int_campanha` (`campanha_id`),
  ADD KEY `idx_mkt_int_envio` (`envio_id`),
  ADD KEY `idx_mkt_int_usuario` (`usuario_id`);

--
-- Índices de tabela `medicos`
--
ALTER TABLE `medicos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_medicos_usuario_crm` (`usuario_id`,`crm`,`uf_crm`),
  ADD KEY `idx_medicos_usuario` (`usuario_id`),
  ADD KEY `idx_medicos_especialidade` (`especialidade_id`),
  ADD KEY `idx_medicos_status` (`status`);

--
-- Índices de tabela `medico_crms`
--
ALTER TABLE `medico_crms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_medico_crms_medico_uf` (`medico_id`,`uf_crm`),
  ADD KEY `idx_medico_crms_medico` (`medico_id`),
  ADD KEY `idx_medico_crms_usuario` (`usuario_id`),
  ADD KEY `idx_medico_crms_crm` (`crm`);

--
-- Índices de tabela `medico_exames`
--
ALTER TABLE `medico_exames`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_medico_exame` (`medico_id`,`tabela_exame_id`),
  ADD KEY `idx_medico_exames_usuario` (`usuario_id`),
  ADD KEY `idx_medico_exames_medico` (`medico_id`),
  ADD KEY `fk_medico_exames_tabela` (`tabela_exame_id`);

--
-- Índices de tabela `mkt_campanhas`
--
ALTER TABLE `mkt_campanhas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mkt_camp_usuario` (`usuario_id`),
  ADD KEY `idx_mkt_camp_status` (`status`),
  ADD KEY `idx_mkt_camp_canal` (`canal`);

--
-- Índices de tabela `mkt_campanha_contatos`
--
ALTER TABLE `mkt_campanha_contatos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token_rastreio` (`token_rastreio`),
  ADD KEY `idx_mkt_cont_campanha` (`campanha_id`),
  ADD KEY `idx_mkt_cont_status` (`status`),
  ADD KEY `idx_mkt_cont_usuario` (`usuario_id`),
  ADD KEY `idx_mkt_cont_token` (`token_rastreio`);

--
-- Índices de tabela `movimentacoes_bancarias`
--
ALTER TABLE `movimentacoes_bancarias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_fitid_conta` (`conta_id`,`fitid`),
  ADD KEY `idx_conta_data` (`conta_id`,`data_lancamento`),
  ADD KEY `idx_tipo` (`tipo`),
  ADD KEY `idx_conciliado` (`conciliado`),
  ADD KEY `idx_importacao` (`importacao_id`);

--
-- Índices de tabela `notas_fiscais`
--
ALTER TABLE `notas_fiscais`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notas_fiscais_usuario` (`usuario_id`),
  ADD KEY `idx_notas_fiscais_cliente` (`cliente_id`),
  ADD KEY `idx_notas_fiscais_numero` (`numero_nf`),
  ADD KEY `idx_notas_fiscais_emissao` (`data_emissao`),
  ADD KEY `idx_nf_asaas_invoice_id` (`asaas_invoice_id`),
  ADD KEY `idx_nf_conta_receber_id` (`conta_receber_id`),
  ADD KEY `idx_nf_origem_emissao` (`origem_emissao`);

--
-- Índices de tabela `notas_fiscais_anexos`
--
ALTER TABLE `notas_fiscais_anexos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_nf_anexos_nota` (`nota_fiscal_id`),
  ADD KEY `idx_nf_anexos_tenant` (`usuario_id`);

--
-- Índices de tabela `notas_fiscais_importacoes`
--
ALTER TABLE `notas_fiscais_importacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_nf_import_usuario` (`usuario_id`);

--
-- Índices de tabela `notificacao_config_alertas`
--
ALTER TABLE `notificacao_config_alertas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_usuario_tipo` (`usuario_id`,`tipo`),
  ADD KEY `idx_ncfg_usuario` (`usuario_id`);

--
-- Índices de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notif_usuario_lida` (`usuario_id`,`lida`),
  ADD KEY `idx_notif_usuario_tipo` (`usuario_id`,`tipo`),
  ADD KEY `idx_notif_referencia` (`referencia_tipo`,`referencia_id`),
  ADD KEY `idx_notif_created` (`created_at`);

--
-- Índices de tabela `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_token_hash` (`token_hash`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Índices de tabela `plano_contas`
--
ALTER TABLE `plano_contas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_plano_contas_usuario` (`usuario_id`),
  ADD KEY `idx_plano_contas_codigo` (`codigo`),
  ADD KEY `idx_plano_contas_conta_pai` (`conta_pai_id`);

--
-- Índices de tabela `portal_clientes`
--
ALTER TABLE `portal_clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_portal_email` (`email`),
  ADD UNIQUE KEY `uk_portal_cliente` (`cliente_id`),
  ADD KEY `idx_portal_ativo` (`ativo`);

--
-- Índices de tabela `portal_clientes_tokens`
--
ALTER TABLE `portal_clientes_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_portal_token` (`token`),
  ADD KEY `idx_portal_token_cliente` (`cliente_id`);

--
-- Índices de tabela `produtos`
--
ALTER TABLE `produtos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_produto_codigo_usuario` (`codigo`,`usuario_id`),
  ADD KEY `idx_produto_usuario` (`usuario_id`),
  ADD KEY `idx_produto_tipo` (`tipo`),
  ADD KEY `idx_produto_categoria` (`categoria`),
  ADD KEY `idx_produto_status` (`status`),
  ADD KEY `idx_produto_fabricante` (`fabricante_id`),
  ADD KEY `idx_produto_anvisa` (`anvisa_registro`),
  ADD KEY `idx_produtos_deprec` (`usuario_id`,`controla_depreciacao`);

--
-- Índices de tabela `produto_codigo_seq`
--
ALTER TABLE `produto_codigo_seq`
  ADD PRIMARY KEY (`usuario_id`);

--
-- Índices de tabela `produto_comissoes`
--
ALTER TABLE `produto_comissoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pcom_produto` (`produto_id`),
  ADD KEY `idx_pcom_colaborador` (`colaborador_id`),
  ADD KEY `idx_pcom_usuario` (`usuario_id`);

--
-- Índices de tabela `produto_componentes`
--
ALTER TABLE `produto_componentes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pc_produto` (`produto_id`),
  ADD KEY `idx_pc_componente` (`componente_id`),
  ADD KEY `idx_pc_usuario` (`usuario_id`);

--
-- Índices de tabela `produto_historico_precos`
--
ALTER TABLE `produto_historico_precos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_php_produto` (`produto_id`);

--
-- Índices de tabela `produto_lotes`
--
ALTER TABLE `produto_lotes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_plote_produto` (`produto_id`),
  ADD KEY `idx_plote_usuario` (`usuario_id`),
  ADD KEY `idx_plote_validade` (`data_validade`),
  ADD KEY `idx_plote_status` (`status`);

--
-- Índices de tabela `produto_movimentacoes`
--
ALTER TABLE `produto_movimentacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pmov_produto` (`produto_id`),
  ADD KEY `idx_pmov_usuario` (`usuario_id`),
  ADD KEY `idx_pmov_tipo` (`tipo`),
  ADD KEY `idx_pmov_created` (`created_at`);

--
-- Índices de tabela `rdv_aprovacoes`
--
ALTER TABLE `rdv_aprovacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rdvaprov_viagem` (`viagem_id`),
  ADD KEY `idx_rdvaprov_usuario` (`usuario_id`);

--
-- Índices de tabela `rdv_categorias`
--
ALTER TABLE `rdv_categorias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rdvcat_usuario` (`usuario_id`),
  ADD KEY `idx_rdvcat_ativo` (`ativo`);

--
-- Índices de tabela `rdv_despesas`
--
ALTER TABLE `rdv_despesas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rdvdesp_viagem` (`viagem_id`),
  ADD KEY `idx_rdvdesp_categoria` (`categoria_id`),
  ADD KEY `idx_rdvdesp_data` (`data_documento`);

--
-- Índices de tabela `rdv_formas_pagamento`
--
ALTER TABLE `rdv_formas_pagamento`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `rdv_historico`
--
ALTER TABLE `rdv_historico`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rdvhist_viagem` (`viagem_id`);

--
-- Índices de tabela `rdv_ocr_logs`
--
ALTER TABLE `rdv_ocr_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rdvocrlog_viagem` (`viagem_id`),
  ADD KEY `idx_rdvocrlog_engine` (`engine`);

--
-- Índices de tabela `rdv_rotas`
--
ALTER TABLE `rdv_rotas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rdvrota_usuario` (`usuario_id`),
  ADD KEY `idx_rdvrota_ativo` (`ativo`);

--
-- Índices de tabela `rdv_rota_clientes`
--
ALTER TABLE `rdv_rota_clientes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rdvrc_rota` (`rota_id`),
  ADD KEY `idx_rdvrc_cliente` (`cliente_id`),
  ADD KEY `idx_rdvrc_lead` (`lead_id`);

--
-- Índices de tabela `rdv_seq`
--
ALTER TABLE `rdv_seq`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_rdvseq_usuario_ano` (`usuario_id`,`ano`);

--
-- Índices de tabela `rdv_viagens`
--
ALTER TABLE `rdv_viagens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_rdv_codigo` (`codigo`),
  ADD KEY `idx_rdv_usuario` (`usuario_id`),
  ADD KEY `idx_rdv_rota` (`rota_id`),
  ADD KEY `idx_rdv_status` (`status`),
  ADD KEY `idx_rdv_periodo` (`periodo_inicio`,`periodo_fim`);

--
-- Índices de tabela `security_two_factor_logs`
--
ALTER TABLE `security_two_factor_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_2fa_log_user` (`user_id`),
  ADD KEY `idx_2fa_log_action` (`action`),
  ADD KEY `idx_2fa_log_created` (`created_at`);

--
-- Índices de tabela `tabela_exames`
--
ALTER TABLE `tabela_exames`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tabela_exames_usuario` (`usuario_id`),
  ADD KEY `idx_tabela_exames_modalidade` (`modalidade`);

--
-- Índices de tabela `tabela_exames_tags`
--
ALTER TABLE `tabela_exames_tags`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tabela_exames_tags_exame` (`exame_id`);

--
-- Índices de tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role` (`role`);

--
-- Índices de tabela `whatsapp_bot_logs`
--
ALTER TABLE `whatsapp_bot_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_wbl_tenant` (`tenant_id`),
  ADD KEY `idx_wbl_status` (`status`),
  ADD KEY `idx_wbl_created` (`created_at`),
  ADD KEY `idx_wbl_integracao` (`integracao_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `apuracao_itens`
--
ALTER TABLE `apuracao_itens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `apuracoes`
--
ALTER TABLE `apuracoes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID único do cliente', AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `clientes_anexos`
--
ALTER TABLE `clientes_anexos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `clientes_contatos`
--
ALTER TABLE `clientes_contatos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID único do contato', AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `cnes_equipamentos`
--
ALTER TABLE `cnes_equipamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `cnes_estabelecimentos`
--
ALTER TABLE `cnes_estabelecimentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `cnes_importacoes`
--
ALTER TABLE `cnes_importacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `cnes_profissionais`
--
ALTER TABLE `cnes_profissionais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `colaboradores`
--
ALTER TABLE `colaboradores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `colaboradores_anexos`
--
ALTER TABLE `colaboradores_anexos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `colaboradores_comissoes`
--
ALTER TABLE `colaboradores_comissoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `configuracoes_financeiras`
--
ALTER TABLE `configuracoes_financeiras`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `config_nfs`
--
ALTER TABLE `config_nfs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `contas_bancarias`
--
ALTER TABLE `contas_bancarias`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `contas_movimentacoes`
--
ALTER TABLE `contas_movimentacoes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `contas_pagar`
--
ALTER TABLE `contas_pagar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `contas_pagar_anexos`
--
ALTER TABLE `contas_pagar_anexos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `contas_receber`
--
ALTER TABLE `contas_receber`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `contas_receber_anexos`
--
ALTER TABLE `contas_receber_anexos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `contratos`
--
ALTER TABLE `contratos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `contratos_anexos`
--
ALTER TABLE `contratos_anexos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `contrato_exames`
--
ALTER TABLE `contrato_exames`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `contrato_modalidades`
--
ALTER TABLE `contrato_modalidades`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `crm_anexos`
--
ALTER TABLE `crm_anexos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `crm_interacoes`
--
ALTER TABLE `crm_interacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `crm_leads`
--
ALTER TABLE `crm_leads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `crm_oportunidades`
--
ALTER TABLE `crm_oportunidades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `crm_oportunidade_modalidades`
--
ALTER TABLE `crm_oportunidade_modalidades`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `crm_propostas`
--
ALTER TABLE `crm_propostas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `crm_proposta_aceite`
--
ALTER TABLE `crm_proposta_aceite`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `crm_proposta_historico`
--
ALTER TABLE `crm_proposta_historico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `crm_proposta_itens`
--
ALTER TABLE `crm_proposta_itens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `crm_transferencias`
--
ALTER TABLE `crm_transferencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `dispositivos_controlid`
--
ALTER TABLE `dispositivos_controlid`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `dispositivos_controlid_leituras`
--
ALTER TABLE `dispositivos_controlid_leituras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `dispositivos_controlid_sync_log`
--
ALTER TABLE `dispositivos_controlid_sync_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `email_alertas`
--
ALTER TABLE `email_alertas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `email_alertas_log`
--
ALTER TABLE `email_alertas_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `empresa_config`
--
ALTER TABLE `empresa_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `equipamentos_cliente`
--
ALTER TABLE `equipamentos_cliente`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `especialidades`
--
ALTER TABLE `especialidades`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `est_movimentacoes`
--
ALTER TABLE `est_movimentacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `est_pedidos_compra`
--
ALTER TABLE `est_pedidos_compra`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `est_pedidos_compra_itens`
--
ALTER TABLE `est_pedidos_compra_itens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `est_pedidos_venda`
--
ALTER TABLE `est_pedidos_venda`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `est_pedidos_venda_itens`
--
ALTER TABLE `est_pedidos_venda_itens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `fornecedores`
--
ALTER TABLE `fornecedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `historico_importacoes_ofx`
--
ALTER TABLE `historico_importacoes_ofx`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `hub_ia_agentes`
--
ALTER TABLE `hub_ia_agentes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `hub_ia_agente_permissoes`
--
ALTER TABLE `hub_ia_agente_permissoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `hub_ia_banco_config`
--
ALTER TABLE `hub_ia_banco_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `hub_ia_conectores`
--
ALTER TABLE `hub_ia_conectores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `hub_ia_conhecimento_chunks`
--
ALTER TABLE `hub_ia_conhecimento_chunks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `hub_ia_conhecimento_documentos`
--
ALTER TABLE `hub_ia_conhecimento_documentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `hub_ia_historico`
--
ALTER TABLE `hub_ia_historico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `hub_ia_logs`
--
ALTER TABLE `hub_ia_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `hub_ia_prompts`
--
ALTER TABLE `hub_ia_prompts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `hub_ia_whatsapp_config`
--
ALTER TABLE `hub_ia_whatsapp_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `integracoes`
--
ALTER TABLE `integracoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `integracoes_logs`
--
ALTER TABLE `integracoes_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `layout_exames`
--
ALTER TABLE `layout_exames`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `manual_artigos`
--
ALTER TABLE `manual_artigos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `manual_categorias`
--
ALTER TABLE `manual_categorias`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `manual_historico`
--
ALTER TABLE `manual_historico`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `manut_ordens_servico`
--
ALTER TABLE `manut_ordens_servico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `manut_os_historico`
--
ALTER TABLE `manut_os_historico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `manut_os_trocas`
--
ALTER TABLE `manut_os_trocas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `marketing_campanhas`
--
ALTER TABLE `marketing_campanhas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `marketing_disparadores`
--
ALTER TABLE `marketing_disparadores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `marketing_envios`
--
ALTER TABLE `marketing_envios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `marketing_interacoes_crm`
--
ALTER TABLE `marketing_interacoes_crm`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `medicos`
--
ALTER TABLE `medicos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `medico_crms`
--
ALTER TABLE `medico_crms`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `medico_exames`
--
ALTER TABLE `medico_exames`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `mkt_campanhas`
--
ALTER TABLE `mkt_campanhas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `mkt_campanha_contatos`
--
ALTER TABLE `mkt_campanha_contatos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `movimentacoes_bancarias`
--
ALTER TABLE `movimentacoes_bancarias`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `notas_fiscais`
--
ALTER TABLE `notas_fiscais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `notas_fiscais_anexos`
--
ALTER TABLE `notas_fiscais_anexos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `notas_fiscais_importacoes`
--
ALTER TABLE `notas_fiscais_importacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `notificacao_config_alertas`
--
ALTER TABLE `notificacao_config_alertas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `plano_contas`
--
ALTER TABLE `plano_contas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `portal_clientes`
--
ALTER TABLE `portal_clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `portal_clientes_tokens`
--
ALTER TABLE `portal_clientes_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `produtos`
--
ALTER TABLE `produtos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `produto_comissoes`
--
ALTER TABLE `produto_comissoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `produto_componentes`
--
ALTER TABLE `produto_componentes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `produto_historico_precos`
--
ALTER TABLE `produto_historico_precos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `produto_lotes`
--
ALTER TABLE `produto_lotes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `produto_movimentacoes`
--
ALTER TABLE `produto_movimentacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `rdv_aprovacoes`
--
ALTER TABLE `rdv_aprovacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `rdv_categorias`
--
ALTER TABLE `rdv_categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `rdv_despesas`
--
ALTER TABLE `rdv_despesas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `rdv_formas_pagamento`
--
ALTER TABLE `rdv_formas_pagamento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `rdv_historico`
--
ALTER TABLE `rdv_historico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `rdv_ocr_logs`
--
ALTER TABLE `rdv_ocr_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `rdv_rotas`
--
ALTER TABLE `rdv_rotas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `rdv_rota_clientes`
--
ALTER TABLE `rdv_rota_clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `rdv_seq`
--
ALTER TABLE `rdv_seq`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `rdv_viagens`
--
ALTER TABLE `rdv_viagens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `security_two_factor_logs`
--
ALTER TABLE `security_two_factor_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `tabela_exames`
--
ALTER TABLE `tabela_exames`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `tabela_exames_tags`
--
ALTER TABLE `tabela_exames_tags`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `whatsapp_bot_logs`
--
ALTER TABLE `whatsapp_bot_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `apuracao_itens`
--
ALTER TABLE `apuracao_itens`
  ADD CONSTRAINT `fk_apuracao_itens_apuracao` FOREIGN KEY (`apuracao_id`) REFERENCES `apuracoes` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `apuracoes`
--
ALTER TABLE `apuracoes`
  ADD CONSTRAINT `fk_apuracoes_contrato` FOREIGN KEY (`contrato_id`) REFERENCES `contratos` (`id`),
  ADD CONSTRAINT `fk_apuracoes_mae_hierarquia` FOREIGN KEY (`apuracao_mae_id`) REFERENCES `apuracoes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `clientes`
--
ALTER TABLE `clientes`
  ADD CONSTRAINT `fk_clientes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`);

--
-- Restrições para tabelas `clientes_contatos`
--
ALTER TABLE `clientes_contatos`
  ADD CONSTRAINT `fk_clientes_contatos_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `colaboradores`
--
ALTER TABLE `colaboradores`
  ADD CONSTRAINT `fk_colaboradores_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_colaboradores_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`);

--
-- Restrições para tabelas `colaboradores_anexos`
--
ALTER TABLE `colaboradores_anexos`
  ADD CONSTRAINT `fk_col_anexos_colaborador` FOREIGN KEY (`colaborador_id`) REFERENCES `colaboradores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_col_anexos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`);

--
-- Restrições para tabelas `colaboradores_comissoes`
--
ALTER TABLE `colaboradores_comissoes`
  ADD CONSTRAINT `fk_col_comissoes_colaborador` FOREIGN KEY (`colaborador_id`) REFERENCES `colaboradores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_col_comissoes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`);

--
-- Restrições para tabelas `contas_movimentacoes`
--
ALTER TABLE `contas_movimentacoes`
  ADD CONSTRAINT `fk_mov_conta_bancaria` FOREIGN KEY (`conta_bancaria_id`) REFERENCES `contas_bancarias` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `contas_pagar`
--
ALTER TABLE `contas_pagar`
  ADD CONSTRAINT `fk_contas_pagar_fornecedor` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedores` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_contas_pagar_plano_conta` FOREIGN KEY (`plano_conta_id`) REFERENCES `plano_contas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_contas_pagar_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`);

--
-- Restrições para tabelas `contas_pagar_anexos`
--
ALTER TABLE `contas_pagar_anexos`
  ADD CONSTRAINT `fk_contas_pagar_anexos_conta` FOREIGN KEY (`conta_pagar_id`) REFERENCES `contas_pagar` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_contas_pagar_anexos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`);

--
-- Restrições para tabelas `contas_receber`
--
ALTER TABLE `contas_receber`
  ADD CONSTRAINT `fk_contas_receber_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_contas_receber_plano_conta` FOREIGN KEY (`plano_conta_id`) REFERENCES `plano_contas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_contas_receber_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`);

--
-- Restrições para tabelas `contratos_anexos`
--
ALTER TABLE `contratos_anexos`
  ADD CONSTRAINT `fk_contratos_anexos_contrato` FOREIGN KEY (`contrato_id`) REFERENCES `contratos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `contrato_exames`
--
ALTER TABLE `contrato_exames`
  ADD CONSTRAINT `fk_ce_contrato` FOREIGN KEY (`contrato_id`) REFERENCES `contratos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ce_tabela_exame` FOREIGN KEY (`tabela_exame_id`) REFERENCES `tabela_exames` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `contrato_modalidades`
--
ALTER TABLE `contrato_modalidades`
  ADD CONSTRAINT `fk_contrato_modalidades_contrato` FOREIGN KEY (`contrato_id`) REFERENCES `contratos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `crm_interacoes`
--
ALTER TABLE `crm_interacoes`
  ADD CONSTRAINT `fk_crm_int_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `crm_leads`
--
ALTER TABLE `crm_leads`
  ADD CONSTRAINT `fk_crm_lead_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `crm_oportunidades`
--
ALTER TABLE `crm_oportunidades`
  ADD CONSTRAINT `fk_crm_op_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_crm_op_lead` FOREIGN KEY (`lead_id`) REFERENCES `crm_leads` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_crm_op_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `crm_oportunidade_modalidades`
--
ALTER TABLE `crm_oportunidade_modalidades`
  ADD CONSTRAINT `fk_crm_op_mod_op` FOREIGN KEY (`oportunidade_id`) REFERENCES `crm_oportunidades` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `crm_proposta_aceite`
--
ALTER TABLE `crm_proposta_aceite`
  ADD CONSTRAINT `fk_aceite_proposta` FOREIGN KEY (`proposta_id`) REFERENCES `crm_propostas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `crm_proposta_historico`
--
ALTER TABLE `crm_proposta_historico`
  ADD CONSTRAINT `fk_hist_proposta` FOREIGN KEY (`proposta_id`) REFERENCES `crm_propostas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `crm_proposta_itens`
--
ALTER TABLE `crm_proposta_itens`
  ADD CONSTRAINT `fk_item_proposta` FOREIGN KEY (`proposta_id`) REFERENCES `crm_propostas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `crm_transferencias`
--
ALTER TABLE `crm_transferencias`
  ADD CONSTRAINT `fk_crm_transf_de` FOREIGN KEY (`de_usuario_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_crm_transf_executor` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_crm_transf_para` FOREIGN KEY (`para_usuario_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `dispositivos_controlid_leituras`
--
ALTER TABLE `dispositivos_controlid_leituras`
  ADD CONSTRAINT `fk_leitura_dispositivo` FOREIGN KEY (`dispositivo_id`) REFERENCES `dispositivos_controlid` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `dispositivos_controlid_sync_log`
--
ALTER TABLE `dispositivos_controlid_sync_log`
  ADD CONSTRAINT `fk_sync_dispositivo` FOREIGN KEY (`dispositivo_id`) REFERENCES `dispositivos_controlid` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `empresa_config`
--
ALTER TABLE `empresa_config`
  ADD CONSTRAINT `fk_empresa_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `especialidades`
--
ALTER TABLE `especialidades`
  ADD CONSTRAINT `fk_especialidades_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `fornecedores`
--
ALTER TABLE `fornecedores`
  ADD CONSTRAINT `fk_fornecedores_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`);

--
-- Restrições para tabelas `historico_importacoes_ofx`
--
ALTER TABLE `historico_importacoes_ofx`
  ADD CONSTRAINT `fk_imp_conta` FOREIGN KEY (`conta_id`) REFERENCES `contas_bancarias` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `integracoes`
--
ALTER TABLE `integracoes`
  ADD CONSTRAINT `fk_integracoes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`);

--
-- Restrições para tabelas `integracoes_logs`
--
ALTER TABLE `integracoes_logs`
  ADD CONSTRAINT `fk_integracoes_logs_integracao` FOREIGN KEY (`integracao_id`) REFERENCES `integracoes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_integracoes_logs_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`);

--
-- Restrições para tabelas `medicos`
--
ALTER TABLE `medicos`
  ADD CONSTRAINT `fk_medicos_especialidade` FOREIGN KEY (`especialidade_id`) REFERENCES `especialidades` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_medicos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `medico_crms`
--
ALTER TABLE `medico_crms`
  ADD CONSTRAINT `fk_medico_crms_medico` FOREIGN KEY (`medico_id`) REFERENCES `medicos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_medico_crms_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `medico_exames`
--
ALTER TABLE `medico_exames`
  ADD CONSTRAINT `fk_medico_exames_medico` FOREIGN KEY (`medico_id`) REFERENCES `medicos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_medico_exames_tabela` FOREIGN KEY (`tabela_exame_id`) REFERENCES `tabela_exames` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `movimentacoes_bancarias`
--
ALTER TABLE `movimentacoes_bancarias`
  ADD CONSTRAINT `fk_mov_conta` FOREIGN KEY (`conta_id`) REFERENCES `contas_bancarias` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_mov_importacao` FOREIGN KEY (`importacao_id`) REFERENCES `historico_importacoes_ofx` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `notas_fiscais`
--
ALTER TABLE `notas_fiscais`
  ADD CONSTRAINT `fk_notas_fiscais_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  ADD CONSTRAINT `fk_notas_fiscais_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`);

--
-- Restrições para tabelas `notas_fiscais_anexos`
--
ALTER TABLE `notas_fiscais_anexos`
  ADD CONSTRAINT `fk_nf_anexo_nota` FOREIGN KEY (`nota_fiscal_id`) REFERENCES `notas_fiscais` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `notas_fiscais_importacoes`
--
ALTER TABLE `notas_fiscais_importacoes`
  ADD CONSTRAINT `fk_nf_import_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`);

--
-- Restrições para tabelas `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `password_reset_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `plano_contas`
--
ALTER TABLE `plano_contas`
  ADD CONSTRAINT `fk_plano_contas_pai` FOREIGN KEY (`conta_pai_id`) REFERENCES `plano_contas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_plano_contas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`);

--
-- Restrições para tabelas `portal_clientes`
--
ALTER TABLE `portal_clientes`
  ADD CONSTRAINT `fk_portal_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `portal_clientes_tokens`
--
ALTER TABLE `portal_clientes_tokens`
  ADD CONSTRAINT `fk_portal_token_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `produtos`
--
ALTER TABLE `produtos`
  ADD CONSTRAINT `fk_produto_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`);

--
-- Restrições para tabelas `produto_codigo_seq`
--
ALTER TABLE `produto_codigo_seq`
  ADD CONSTRAINT `fk_pcs_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `produto_comissoes`
--
ALTER TABLE `produto_comissoes`
  ADD CONSTRAINT `fk_pcom_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pcom_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`);

--
-- Restrições para tabelas `produto_componentes`
--
ALTER TABLE `produto_componentes`
  ADD CONSTRAINT `fk_pc_componente` FOREIGN KEY (`componente_id`) REFERENCES `produtos` (`id`),
  ADD CONSTRAINT `fk_pc_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pc_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`);

--
-- Restrições para tabelas `produto_historico_precos`
--
ALTER TABLE `produto_historico_precos`
  ADD CONSTRAINT `fk_php_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `produto_lotes`
--
ALTER TABLE `produto_lotes`
  ADD CONSTRAINT `fk_plote_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`),
  ADD CONSTRAINT `fk_plote_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`);

--
-- Restrições para tabelas `produto_movimentacoes`
--
ALTER TABLE `produto_movimentacoes`
  ADD CONSTRAINT `fk_pmov_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`),
  ADD CONSTRAINT `fk_pmov_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`);

--
-- Restrições para tabelas `tabela_exames`
--
ALTER TABLE `tabela_exames`
  ADD CONSTRAINT `fk_tabela_exames_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `tabela_exames_tags`
--
ALTER TABLE `tabela_exames_tags`
  ADD CONSTRAINT `fk_tabela_exames_tags_exame` FOREIGN KEY (`exame_id`) REFERENCES `tabela_exames` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
