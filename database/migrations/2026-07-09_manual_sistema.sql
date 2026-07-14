-- ============================================================
-- Manual do Sistema (Wiki)
-- Criado em: 2026-07-09
-- Compatível com MySQL 5.7 / HostGator
-- ============================================================

-- Categorias do manual (módulos do sistema)
CREATE TABLE IF NOT EXISTS manual_categorias (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug          VARCHAR(80)  NOT NULL,
    titulo        VARCHAR(120) NOT NULL,
    descricao     TEXT         NULL,
    icone         VARCHAR(60)  NOT NULL DEFAULT 'fas fa-book',
    cor           VARCHAR(20)  NOT NULL DEFAULT '#1e40af',
    ordem         SMALLINT     NOT NULL DEFAULT 0,
    ativo         TINYINT(1)   NOT NULL DEFAULT 1,
    criado_em     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_manual_cat_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Artigos do manual
CREATE TABLE IF NOT EXISTS manual_artigos (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    categoria_id   INT UNSIGNED NOT NULL,
    slug           VARCHAR(120) NOT NULL,
    titulo         VARCHAR(200) NOT NULL,
    resumo         VARCHAR(400) NULL,
    conteudo       LONGTEXT     NOT NULL,
    ordem          SMALLINT     NOT NULL DEFAULT 0,
    publicado      TINYINT(1)   NOT NULL DEFAULT 1,
    criado_por     INT UNSIGNED NULL,
    atualizado_por INT UNSIGNED NULL,
    criado_em      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_manual_art_slug (slug),
    KEY idx_manual_art_cat (categoria_id),
    KEY idx_manual_art_pub (publicado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Histórico de edições
CREATE TABLE IF NOT EXISTS manual_historico (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    artigo_id    INT UNSIGNED NOT NULL,
    usuario_id   INT UNSIGNED NULL,
    conteudo     LONGTEXT     NOT NULL,
    titulo       VARCHAR(200) NOT NULL,
    criado_em    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_manual_hist_artigo (artigo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ============================================================
-- Dados iniciais: categorias de cada módulo do ERP
-- ============================================================
INSERT IGNORE INTO manual_categorias (slug, titulo, descricao, icone, cor, ordem) VALUES
('primeiros-passos',    'Primeiros Passos',        'Como acessar e configurar o sistema pela primeira vez.',                    'fas fa-rocket',            '#0f766e', 1),
('dashboard',           'Dashboard',               'Visão geral dos indicadores e atalhos do sistema.',                         'fas fa-tachometer-alt',    '#1d4ed8', 2),
('clientes',            'Clientes',                'Cadastro, edição, equipamentos, contatos e histórico de clientes.',         'fas fa-users',             '#7c3aed', 3),
('fornecedores',        'Fornecedores',            'Cadastro e gestão de fornecedores.',                                        'fas fa-truck',             '#b45309', 4),
('corpo-clinico',       'Corpo Clínico',           'Cadastro de médicos, especialistas e colaboradores clínicos.',              'fas fa-user-md',           '#0891b2', 5),
('colaboradores',       'Colaboradores',           'Gestão de colaboradores internos.',                                         'fas fa-id-badge',          '#4f46e5', 6),
('contratos',           'Contratos',               'Criação e gestão de contratos com clientes.',                               'fas fa-file-contract',     '#065f46', 7),
('financeiro',          'Financeiro',              'Contas a pagar, contas a receber, plano de contas e fluxo de caixa.',       'fas fa-dollar-sign',       '#166534', 8),
('faturamento',         'Faturamento',             'Notas fiscais de serviço (NFS-e), emissão via Asaas e apurações.',          'fas fa-file-invoice',      '#9a3412', 9),
('estoque',             'Estoque',                 'Controle de produtos, movimentações e inventário.',                         'fas fa-boxes',             '#7e22ce', 10),
('crm',                 'CRM',                     'Leads, oportunidades, funil de vendas e propostas comerciais.',             'fas fa-handshake',         '#0369a1', 11),
('marketing',           'Marketing',               'Campanhas de e-mail e disparadores automáticos.',                           'fas fa-bullhorn',          '#be185d', 12),
('rdv',                 'RDV — Despesas de Viagem','Registro de despesas de viagem, rotas comerciais e aprovações.',            'fas fa-route',             '#b45309', 13),
('manutencao',          'Manutenção',              'Ordens de serviço, itens trocados e histórico de atendimentos.',            'fas fa-tools',             '#374151', 14),
('configuracoes',       'Configurações',           'Usuários, permissões, integrações, notas fiscais e parâmetros gerais.',     'fas fa-cog',               '#6b7280', 15),
('cnes',                'CNES',                    'Importação e consulta de dados do Cadastro Nacional de Estabelecimentos.',  'fas fa-hospital',          '#0c4a6e', 16);

-- ============================================================
-- Artigos iniciais por módulo
-- ============================================================

-- PRIMEIROS PASSOS
INSERT IGNORE INTO manual_artigos (categoria_id, slug, titulo, resumo, conteudo, ordem) VALUES
(
  (SELECT id FROM manual_categorias WHERE slug='primeiros-passos'),
  'como-acessar-o-sistema',
  'Como acessar o sistema',
  'Aprenda a fazer login, recuperar senha e realizar o primeiro acesso.',
  '<h2>Acesso ao Sistema</h2>
<p>O ERP InLaudo é acessado pelo endereço <strong>erp.inlaudo.com.br/login</strong>. Na tela de login, informe seu <strong>e-mail</strong> e <strong>senha</strong> cadastrados pelo administrador.</p>
<h3>Visualizar senha</h3>
<p>Clique no ícone de olho (<i class="fa fa-eye"></i>) ao lado direito do campo Senha para exibir ou ocultar os caracteres digitados.</p>
<h3>Esqueceu a senha?</h3>
<p>Clique em <strong>"Esqueceu sua senha?"</strong> na tela de login. Informe seu e-mail e um link de redefinição será enviado.</p>
<h3>Primeiro acesso</h3>
<p>Clique em <strong>"Primeiro acesso"</strong> para criar sua senha inicial. Informe o e-mail cadastrado pelo administrador e siga as instruções recebidas por e-mail.</p>
<h3>Sair do sistema</h3>
<p>Clique no seu nome no canto superior direito → <strong>"Sair do Sistema"</strong>.</p>',
  1
),
(
  (SELECT id FROM manual_categorias WHERE slug='primeiros-passos'),
  'configuracao-inicial',
  'Configuração inicial do sistema',
  'Passo a passo para configurar o ERP após o primeiro acesso.',
  '<h2>Configuração Inicial</h2>
<p>Após o primeiro acesso, recomenda-se seguir a ordem abaixo para configurar o sistema corretamente:</p>
<ol>
  <li><strong>Configurações Gerais</strong> — Acesse <em>Menu do usuário → Configurações → aba Geral</em>. Preencha o nome da empresa, CNPJ, endereço e logo.</li>
  <li><strong>Usuários</strong> — Acesse <em>Configurações → aba Usuários</em>. Crie os usuários que terão acesso ao sistema com seus respectivos perfis (Administrador ou Usuário).</li>
  <li><strong>Integrações</strong> — Configure a chave de API do Asaas em <em>Configurações → aba Financeiro</em> para habilitar cobranças e emissão de NFS-e.</li>
  <li><strong>Notas Fiscais</strong> — Acesse <em>Configurações → aba Notas Fiscais</em>. Preencha os dados de emissão: código de serviço municipal, CNAE, alíquotas e NBS.</li>
  <li><strong>Plano de Contas</strong> — Acesse <em>Financeiro → Plano de Contas</em> e configure as categorias de receita e despesa.</li>
</ol>',
  2
);

-- DASHBOARD
INSERT IGNORE INTO manual_artigos (categoria_id, slug, titulo, resumo, conteudo, ordem) VALUES
(
  (SELECT id FROM manual_categorias WHERE slug='dashboard'),
  'visao-geral-dashboard',
  'Visão geral do Dashboard',
  'Entenda os indicadores e atalhos disponíveis na tela inicial.',
  '<h2>Dashboard</h2>
<p>O Dashboard é a tela inicial do ERP. Ele exibe os principais indicadores financeiros e operacionais em tempo real.</p>
<h3>Como acessar</h3>
<p>Clique em <strong>Dashboard</strong> no menu lateral esquerdo, ou acesse <code>/dashboard</code>.</p>
<h3>Indicadores disponíveis</h3>
<ul>
  <li><strong>Contas a Receber</strong> — Total em aberto, vencidas e recebidas no mês.</li>
  <li><strong>Contas a Pagar</strong> — Total em aberto e vencidas.</li>
  <li><strong>Fluxo de Caixa</strong> — Saldo projetado dos próximos dias.</li>
  <li><strong>Contratos Ativos</strong> — Quantidade de contratos vigentes.</li>
  <li><strong>Ordens de Serviço</strong> — OS abertas e em andamento.</li>
</ul>',
  1
);

-- CLIENTES
INSERT IGNORE INTO manual_artigos (categoria_id, slug, titulo, resumo, conteudo, ordem) VALUES
(
  (SELECT id FROM manual_categorias WHERE slug='clientes'),
  'cadastrar-cliente',
  'Como cadastrar um cliente',
  'Passo a passo para criar um novo cliente no sistema.',
  '<h2>Cadastrar Cliente</h2>
<h3>Como acessar</h3>
<p>Menu lateral → <strong>Cadastros → Clientes</strong> → botão <strong>"+ Novo Cliente"</strong>.</p>
<h3>Campos principais</h3>
<ul>
  <li><strong>Razão Social / Nome</strong> — Nome completo ou razão social.</li>
  <li><strong>Nome Fantasia</strong> — Nome comercial (opcional).</li>
  <li><strong>CPF/CNPJ</strong> — Documento do cliente. O sistema valida o formato automaticamente.</li>
  <li><strong>E-mail</strong> — Usado para envio de cobranças e notificações.</li>
  <li><strong>Telefone / Celular</strong> — Contato principal.</li>
  <li><strong>Endereço</strong> — Preencha o CEP para preenchimento automático do endereço.</li>
</ul>
<h3>Busca automática por CNPJ</h3>
<p>Ao digitar um CNPJ válido, o sistema consulta a Receita Federal e preenche automaticamente os dados cadastrais.</p>
<h3>Abas do cadastro</h3>
<ul>
  <li><strong>Geral</strong> — Dados principais.</li>
  <li><strong>Contatos</strong> — Adicione múltiplos contatos (nome, cargo, e-mail, telefone).</li>
  <li><strong>Equipamentos</strong> — Equipamentos instalados no cliente.</li>
  <li><strong>Financeiro</strong> — Histórico de cobranças e contratos.</li>
  <li><strong>Anexos</strong> — Documentos e arquivos relacionados.</li>
</ul>',
  1
),
(
  (SELECT id FROM manual_categorias WHERE slug='clientes'),
  'editar-excluir-cliente',
  'Editar e excluir clientes',
  'Como atualizar dados ou remover um cliente do sistema.',
  '<h2>Editar Cliente</h2>
<p>Na listagem de clientes, clique no ícone de <strong>lápis</strong> (<i class="fas fa-edit"></i>) ao lado do cliente desejado, ou clique no nome do cliente para abrir o cadastro e depois em <strong>"Editar"</strong>.</p>
<h2>Excluir Cliente</h2>
<p>Clique no ícone de <strong>lixeira</strong> (<i class="fas fa-trash"></i>). O sistema solicitará confirmação antes de excluir. Clientes com contratos, cobranças ou ordens de serviço vinculados não podem ser excluídos.</p>',
  2
);

-- FINANCEIRO
INSERT IGNORE INTO manual_artigos (categoria_id, slug, titulo, resumo, conteudo, ordem) VALUES
(
  (SELECT id FROM manual_categorias WHERE slug='financeiro'),
  'contas-a-receber',
  'Contas a Receber',
  'Como criar, editar e gerenciar contas a receber.',
  '<h2>Contas a Receber</h2>
<h3>Como acessar</h3>
<p>Menu lateral → <strong>Financeiro → Contas a Receber</strong>.</p>
<h3>Criar nova cobrança</h3>
<ol>
  <li>Clique em <strong>"+ Nova Conta"</strong>.</li>
  <li>Selecione o <strong>cliente</strong>, informe o <strong>valor</strong>, <strong>vencimento</strong> e <strong>descrição</strong>.</li>
  <li>Selecione a <strong>forma de pagamento</strong> (Boleto, PIX, Cartão, etc.).</li>
  <li>Se desejar emitir uma NFS-e automaticamente, marque <strong>"Emitir NF Avulsa"</strong>.</li>
  <li>Clique em <strong>Salvar</strong>.</li>
</ol>
<h3>Integração com Asaas</h3>
<p>Quando a integração com o Asaas está configurada, o sistema cria automaticamente a cobrança no Asaas e sincroniza o status de pagamento.</p>
<h3>Status das cobranças</h3>
<ul>
  <li><span style="color:#166534">Pago</span> — Pagamento confirmado.</li>
  <li><span style="color:#b45309">Pendente</span> — Aguardando pagamento.</li>
  <li><span style="color:#991b1b">Vencido</span> — Prazo expirado sem pagamento.</li>
  <li><span style="color:#6b7280">Cancelado</span> — Cobrança cancelada.</li>
</ul>',
  1
),
(
  (SELECT id FROM manual_categorias WHERE slug='financeiro'),
  'contas-a-pagar',
  'Contas a Pagar',
  'Como registrar e controlar despesas e contas a pagar.',
  '<h2>Contas a Pagar</h2>
<h3>Como acessar</h3>
<p>Menu lateral → <strong>Financeiro → Contas a Pagar</strong>.</p>
<h3>Registrar despesa</h3>
<ol>
  <li>Clique em <strong>"+ Nova Conta"</strong>.</li>
  <li>Informe o <strong>fornecedor</strong> (ou beneficiário), <strong>valor</strong>, <strong>vencimento</strong> e <strong>categoria</strong> do plano de contas.</li>
  <li>Anexe o comprovante se necessário.</li>
  <li>Clique em <strong>Salvar</strong>.</li>
</ol>
<h3>Marcar como pago</h3>
<p>Na listagem, clique no ícone de <strong>check</strong> (<i class="fas fa-check-circle"></i>) para registrar o pagamento. Informe a data e o valor efetivamente pago.</p>',
  2
),
(
  (SELECT id FROM manual_categorias WHERE slug='financeiro'),
  'fluxo-de-caixa',
  'Fluxo de Caixa',
  'Como visualizar e analisar o fluxo de caixa.',
  '<h2>Fluxo de Caixa</h2>
<h3>Como acessar</h3>
<p>Menu lateral → <strong>Financeiro → Fluxo de Caixa</strong>.</p>
<p>O fluxo de caixa consolida todas as entradas (contas a receber pagas) e saídas (contas a pagar pagas) em uma visão cronológica. Use os filtros de período para analisar intervalos específicos.</p>',
  3
);

-- FATURAMENTO
INSERT IGNORE INTO manual_artigos (categoria_id, slug, titulo, resumo, conteudo, ordem) VALUES
(
  (SELECT id FROM manual_categorias WHERE slug='faturamento'),
  'emitir-nota-fiscal',
  'Emitir Nota Fiscal de Serviço (NFS-e)',
  'Como emitir uma NFS-e manualmente ou via Asaas.',
  '<h2>Emitir Nota Fiscal de Serviço</h2>
<h3>Pré-requisitos</h3>
<ul>
  <li>Chave de API do Asaas configurada em <em>Configurações → Financeiro</em>.</li>
  <li>Dados de NFS-e preenchidos em <em>Configurações → Notas Fiscais</em> (código de serviço, CNAE, alíquotas, NBS).</li>
</ul>
<h3>Como acessar</h3>
<p>Menu lateral → <strong>Faturamento → Notas Fiscais</strong> → botão <strong>"+ Nova Nota"</strong>.</p>
<h3>Emissão via Asaas</h3>
<ol>
  <li>Selecione o <strong>cliente</strong>.</li>
  <li>Informe o <strong>valor</strong> e a <strong>data de emissão</strong>.</li>
  <li>Ative o switch <strong>"Emitir NFS-e via Asaas ao salvar"</strong>.</li>
  <li>Confirme a descrição do serviço e o código municipal.</li>
  <li>Clique em <strong>Criar Nota</strong>.</li>
</ol>
<p>O sistema enviará a nota ao Asaas e exibirá o status em tempo real na tela de detalhes. Quando a prefeitura autorizar, o PDF e XML ficam disponíveis.</p>
<h3>Status da nota</h3>
<ul>
  <li><strong>Rascunho</strong> — Salva localmente, não enviada ao Asaas.</li>
  <li><strong>Agendada</strong> — Enviada ao Asaas, aguardando processamento.</li>
  <li><strong>Emitida</strong> — Autorizada pela prefeitura.</li>
  <li><strong>Erro na Emissão</strong> — Falha no envio. Verifique as pendências exibidas.</li>
  <li><strong>Cancelada</strong> — Nota cancelada.</li>
</ul>',
  1
),
(
  (SELECT id FROM manual_categorias WHERE slug='faturamento'),
  'nf-avulsa-contas-receber',
  'NF Avulsa via Contas a Receber',
  'Como emitir NFS-e automaticamente ao criar uma conta a receber.',
  '<h2>NF Avulsa via Contas a Receber</h2>
<p>É possível emitir uma NFS-e automaticamente ao criar ou salvar uma conta a receber.</p>
<h3>Como usar</h3>
<ol>
  <li>Acesse <strong>Financeiro → Contas a Receber → Nova Conta</strong>.</li>
  <li>Na aba <strong>Dados Principais</strong>, marque o checkbox <strong>"Emitir NF Avulsa"</strong>.</li>
  <li>Preencha os demais dados e salve.</li>
  <li>O sistema emitirá a NFS-e via Asaas automaticamente.</li>
  <li>O status da nota aparece no painel abaixo do checkbox e também em <strong>Faturamento → Notas Fiscais</strong>.</li>
</ol>',
  2
);

-- CRM
INSERT IGNORE INTO manual_artigos (categoria_id, slug, titulo, resumo, conteudo, ordem) VALUES
(
  (SELECT id FROM manual_categorias WHERE slug='crm'),
  'leads-e-oportunidades',
  'Leads e Oportunidades',
  'Como gerenciar leads e oportunidades no funil de vendas.',
  '<h2>Leads e Oportunidades</h2>
<h3>Como acessar</h3>
<p>Menu lateral → <strong>CRM</strong>.</p>
<h3>Leads</h3>
<p>Leads são potenciais clientes ainda em prospecção. Cadastre um lead com nome, empresa, e-mail e telefone. Ao qualificar o lead, converta-o em cliente.</p>
<h3>Oportunidades</h3>
<p>Oportunidades representam negociações em andamento. Cada oportunidade possui:</p>
<ul>
  <li>Etapa no funil (Prospecção, Qualificação, Proposta, Negociação, Fechamento).</li>
  <li>Valor estimado.</li>
  <li>Responsável.</li>
  <li>Data prevista de fechamento.</li>
</ul>
<h3>Propostas</h3>
<p>Gere propostas comerciais diretamente de uma oportunidade. As propostas podem ser enviadas por e-mail e aceitas pelo cliente via link público.</p>',
  1
);

-- MANUTENÇÃO
INSERT IGNORE INTO manual_artigos (categoria_id, slug, titulo, resumo, conteudo, ordem) VALUES
(
  (SELECT id FROM manual_categorias WHERE slug='manutencao'),
  'ordens-de-servico',
  'Ordens de Serviço',
  'Como criar e gerenciar ordens de serviço.',
  '<h2>Ordens de Serviço</h2>
<h3>Como acessar</h3>
<p>Menu lateral → <strong>Manutenção → Ordens de Serviço</strong>.</p>
<h3>Criar OS</h3>
<ol>
  <li>Clique em <strong>"+ Nova OS"</strong>.</li>
  <li>Selecione o <strong>cliente</strong> e o <strong>equipamento</strong>.</li>
  <li>Informe o <strong>tipo</strong> (Corretiva, Preventiva, Instalação), <strong>técnico responsável</strong> e <strong>motivo do chamado</strong>.</li>
  <li>Clique em <strong>Salvar</strong>.</li>
</ol>
<h3>Adicionar itens trocados / serviços</h3>
<p>Na tela de detalhes da OS, clique em <strong>"+ Adicionar Item"</strong>. Selecione o produto do estoque (ou informe manualmente), quantidade, preço unitário e vida útil.</p>
<h3>Proposta automática</h3>
<p>Ao adicionar itens, o sistema gera automaticamente uma proposta CRM vinculada à OS com os valores dos produtos e serviços.</p>',
  1
);

-- RDV
INSERT IGNORE INTO manual_artigos (categoria_id, slug, titulo, resumo, conteudo, ordem) VALUES
(
  (SELECT id FROM manual_categorias WHERE slug='rdv'),
  'criar-rota-comercial',
  'Criar uma Rota Comercial',
  'Como criar rotas e associar clientes para viagens comerciais.',
  '<h2>Rotas Comerciais</h2>
<h3>Como acessar</h3>
<p>Menu lateral → <strong>RDV → Rotas</strong>.</p>
<h3>Tipos de rota</h3>
<ul>
  <li><strong>Rota Padrão</strong> — Vincula clientes e/ou leads da base do ERP. Ideal para visitas planejadas a múltiplos clientes em uma região.</li>
  <li><strong>Rota Livre</strong> — Sem controle de clientes. Ideal para viagens avulsas.</li>
</ul>
<h3>Criar rota</h3>
<ol>
  <li>Clique em <strong>"+ Nova Rota"</strong>.</li>
  <li>Informe o nome (ex: "Triângulo Mineiro"), tipo, região e descrição.</li>
  <li>Salve e, na tela de detalhes, adicione os clientes/leads que serão visitados.</li>
</ol>',
  1
),
(
  (SELECT id FROM manual_categorias WHERE slug='rdv'),
  'registrar-viagem-despesas',
  'Registrar Viagem e Despesas',
  'Como criar uma viagem e lançar despesas com comprovantes.',
  '<h2>Registrar Viagem e Despesas</h2>
<h3>Como acessar</h3>
<p>Menu lateral → <strong>RDV → Viagens</strong>.</p>
<h3>Criar viagem</h3>
<ol>
  <li>Clique em <strong>"+ Nova Viagem"</strong>.</li>
  <li>Selecione a <strong>rota</strong>, o <strong>colaborador</strong>, as datas de saída e retorno e o orçamento previsto.</li>
  <li>Salve. A viagem inicia com status <strong>Planejada</strong>.</li>
</ol>
<h3>Lançar despesas</h3>
<ol>
  <li>Na tela de detalhes da viagem, clique em <strong>"+ Adicionar Despesa"</strong>.</li>
  <li>Selecione a categoria (Combustível, Hospedagem, Alimentação, Pedágio, etc.).</li>
  <li>Informe o valor, data e forma de pagamento.</li>
  <li>Anexe o comprovante (foto ou PDF).</li>
  <li>Salve.</li>
</ol>
<h3>Fluxo de aprovação</h3>
<p>Ao finalizar a viagem, submeta para aprovação. O aprovador poderá aprovar ou rejeitar com justificativa. Após aprovação, o sistema pode gerar uma conta a pagar no módulo financeiro.</p>',
  2
);

-- CONFIGURAÇÕES
INSERT IGNORE INTO manual_artigos (categoria_id, slug, titulo, resumo, conteudo, ordem) VALUES
(
  (SELECT id FROM manual_categorias WHERE slug='configuracoes'),
  'gerenciar-usuarios',
  'Gerenciar Usuários',
  'Como criar, editar e desativar usuários do sistema.',
  '<h2>Gerenciar Usuários</h2>
<h3>Como acessar</h3>
<p>Menu do usuário (canto superior direito) → <strong>Configurações → aba Usuários</strong>.</p>
<h3>Criar usuário</h3>
<ol>
  <li>Clique em <strong>"+ Novo Usuário"</strong>.</li>
  <li>Informe nome, e-mail e perfil (Administrador ou Usuário).</li>
  <li>O sistema enviará um e-mail de primeiro acesso para o usuário criar sua senha.</li>
</ol>
<h3>Desativar usuário</h3>
<p>Clique no toggle de status ao lado do usuário. Usuários desativados não conseguem fazer login.</p>
<h3>Redefinir senha</h3>
<p>Clique em <strong>"Redefinir Senha"</strong> para enviar um novo link de redefinição ao e-mail do usuário.</p>',
  1
),
(
  (SELECT id FROM manual_categorias WHERE slug='configuracoes'),
  'configurar-notas-fiscais',
  'Configurar Notas Fiscais',
  'Como configurar os dados para emissão de NFS-e.',
  '<h2>Configurar Notas Fiscais</h2>
<h3>Como acessar</h3>
<p>Menu do usuário → <strong>Configurações → aba Notas Fiscais</strong>.</p>
<h3>Campos obrigatórios</h3>
<ul>
  <li><strong>Código de Serviço Municipal</strong> — Código da lista de serviços do município.</li>
  <li><strong>CNAE</strong> — Código da atividade econômica da empresa.</li>
  <li><strong>NBS</strong> — Nomenclatura Brasileira de Serviços (ex: 1.07.00.00.00).</li>
  <li><strong>Alíquota ISS</strong> — Percentual do Imposto Sobre Serviços.</li>
  <li><strong>Série</strong> — Série da nota fiscal (padrão: 1).</li>
</ul>
<p>Após preencher, clique em <strong>Salvar</strong>. Esses dados serão usados automaticamente em todas as emissões de NFS-e.</p>',
  2
);

-- MARKETING
INSERT IGNORE INTO manual_artigos (categoria_id, slug, titulo, resumo, conteudo, ordem) VALUES
(
  (SELECT id FROM manual_categorias WHERE slug='marketing'),
  'campanhas-de-email',
  'Campanhas de E-mail',
  'Como criar e enviar campanhas de e-mail para clientes.',
  '<h2>Campanhas de E-mail</h2>
<h3>Como acessar</h3>
<p>Menu lateral → <strong>Marketing → Campanhas</strong>.</p>
<h3>Criar campanha</h3>
<ol>
  <li>Clique em <strong>"+ Nova Campanha"</strong>.</li>
  <li>Defina o nome, assunto do e-mail e o segmento de destinatários.</li>
  <li>Personalize o conteúdo do e-mail no editor.</li>
  <li>Envie um e-mail de teste antes do disparo final.</li>
  <li>Clique em <strong>Disparar Campanha</strong>.</li>
</ol>
<h3>Disparadores Automáticos</h3>
<p>Acesse <strong>Marketing → Disparadores</strong> para criar envios automáticos baseados em eventos (ex: vencimento de contrato, aniversário do cliente).</p>',
  1
);

-- ESTOQUE
INSERT IGNORE INTO manual_artigos (categoria_id, slug, titulo, resumo, conteudo, ordem) VALUES
(
  (SELECT id FROM manual_categorias WHERE slug='estoque'),
  'controle-de-estoque',
  'Controle de Estoque',
  'Como cadastrar produtos e controlar movimentações.',
  '<h2>Controle de Estoque</h2>
<h3>Como acessar</h3>
<p>Menu lateral → <strong>Estoque</strong>.</p>
<h3>Cadastrar produto</h3>
<ol>
  <li>Clique em <strong>"+ Novo Produto"</strong>.</li>
  <li>Informe código, descrição, unidade, preço de custo e preço de venda.</li>
  <li>Defina o estoque mínimo para alertas de reposição.</li>
</ol>
<h3>Movimentações</h3>
<p>Entradas e saídas de estoque são registradas automaticamente quando produtos são utilizados em Ordens de Serviço ou quando são cadastradas entradas manuais.</p>',
  1
);
