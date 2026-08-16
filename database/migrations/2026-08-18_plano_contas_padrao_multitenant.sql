-- ============================================================================
-- ERP IMAGINIFLOW | Plano de contas padrão por tenant
-- Data: 2026-08-18
-- Alvo: MySQL 5.7 / MariaDB 5.7 | HostGator compartilhado
--
-- Objetivo:
-- 1. Identificar contas importadas pelo modelo padrão sem bloquear edições.
-- 2. Garantir unicidade da importação por tenant.
-- 3. Copiar o plano para cada empresa SaaS ativa que possua usuário master.
--
-- ATENÇÃO: faça backup antes de executar em produção.
-- Pré-verificações manuais:
--   SHOW COLUMNS FROM plano_contas LIKE 'modelo_padrao_codigo';
--   SHOW INDEX FROM plano_contas WHERE Key_name = 'uq_plano_contas_tenant_modelo';
-- ============================================================================

-- Execute somente se a coluna ainda não existir.
ALTER TABLE `plano_contas`
  ADD COLUMN `modelo_padrao_codigo` VARCHAR(30) NULL DEFAULT NULL
  COMMENT 'Identificador técnico do plano padrão, sem bloquear edição pelo tenant'
  AFTER `conta_pai_id`;

-- Execute somente se o índice ainda não existir.
-- Permite múltiplas contas manuais (NULL) e apenas uma cópia de cada item padrão por tenant.
ALTER TABLE `plano_contas`
  ADD UNIQUE INDEX `uq_plano_contas_tenant_modelo` (`tenant_id`, `modelo_padrao_codigo`);

-- Copia o plano básico apenas para tenants ativos de clientes.
-- INSERT IGNORE torna a operação idempotente: contas já importadas não são repetidas.
INSERT IGNORE INTO `plano_contas`
    (`tenant_id`, `usuario_id`, `codigo`, `nome`, `tipo`, `nivel`, `conta_pai_id`, `modelo_padrao_codigo`, `status`)
SELECT t.`id`, t.`master_user_id`, m.`codigo`, m.`nome`, m.`tipo`, m.`nivel`, NULL, m.`modelo`, 'ativo'
FROM `tenants` t
INNER JOIN `users` u ON u.`id` = t.`master_user_id`
CROSS JOIN (
    SELECT 'R1' AS modelo, NULL AS pai, '1' AS codigo, 'RECEITAS' AS nome, 'Receita' AS tipo, 1 AS nivel
    UNION ALL SELECT 'R101', 'R1', '1.01', 'Serviços de Saúde', 'Receita', 2
    UNION ALL SELECT 'R10101', 'R101', '1.01.001', 'Diagnóstico por Imagem e Laudos', 'Receita', 3
    UNION ALL SELECT 'R10102', 'R101', '1.01.002', 'Consultas e Procedimentos', 'Receita', 3
    UNION ALL SELECT 'R10103', 'R101', '1.01.003', 'Telemedicina e Segunda Opinião', 'Receita', 3
    UNION ALL SELECT 'R10104', 'R101', '1.01.004', 'Locação de Equipamentos Médicos', 'Receita', 3
    UNION ALL SELECT 'R102', 'R1', '1.02', 'Tecnologia e Sistemas de Gestão', 'Receita', 2
    UNION ALL SELECT 'R10201', 'R102', '1.02.001', 'Licenças e Assinaturas de Software', 'Receita', 3
    UNION ALL SELECT 'R10202', 'R102', '1.02.002', 'Implantação e Parametrização', 'Receita', 3
    UNION ALL SELECT 'R10203', 'R102', '1.02.003', 'Suporte e Manutenção de Software', 'Receita', 3
    UNION ALL SELECT 'R10204', 'R102', '1.02.004', 'Integrações, APIs e Projetos Especiais', 'Receita', 3
    UNION ALL SELECT 'R103', 'R1', '1.03', 'Comercialização de Equipamentos Médicos', 'Receita', 2
    UNION ALL SELECT 'R10301', 'R103', '1.03.001', 'Venda de Equipamentos Médicos', 'Receita', 3
    UNION ALL SELECT 'R10302', 'R103', '1.03.002', 'Manutenção de Equipamentos Médicos', 'Receita', 3
    UNION ALL SELECT 'R10303', 'R103', '1.03.003', 'Venda de Peças e Acessórios', 'Receita', 3
    UNION ALL SELECT 'R104', 'R1', '1.04', 'Outras Receitas', 'Receita', 2
    UNION ALL SELECT 'R10401', 'R104', '1.04.001', 'Receitas Financeiras', 'Receita', 3
    UNION ALL SELECT 'R10402', 'R104', '1.04.002', 'Reembolsos e Recuperações', 'Receita', 3
    UNION ALL SELECT 'D2', NULL, '2', 'DESPESAS', 'Despesa', 1
    UNION ALL SELECT 'D201', 'D2', '2.01', 'Custos Assistenciais Diretos', 'Despesa', 2
    UNION ALL SELECT 'D20101', 'D201', '2.01.001', 'Profissionais de Saúde e Plantões', 'Despesa', 3
    UNION ALL SELECT 'D20102', 'D201', '2.01.002', 'Laudos e Serviços Terceirizados', 'Despesa', 3
    UNION ALL SELECT 'D20103', 'D201', '2.01.003', 'Materiais, Insumos e Contrastes', 'Despesa', 3
    UNION ALL SELECT 'D20104', 'D201', '2.01.004', 'Exames e Serviços Diagnósticos Terceirizados', 'Despesa', 3
    UNION ALL SELECT 'D20105', 'D201', '2.01.005', 'Comissões e Repasses Clínicos', 'Despesa', 3
    UNION ALL SELECT 'D202', 'D2', '2.02', 'Equipamentos Médicos', 'Despesa', 2
    UNION ALL SELECT 'D20201', 'D202', '2.02.001', 'Aquisição e Locação de Equipamentos', 'Despesa', 3
    UNION ALL SELECT 'D20202', 'D202', '2.02.002', 'Manutenção Preventiva e Corretiva', 'Despesa', 3
    UNION ALL SELECT 'D20203', 'D202', '2.02.003', 'Calibração e Controle de Qualidade', 'Despesa', 3
    UNION ALL SELECT 'D20204', 'D202', '2.02.004', 'Peças, Acessórios e Suprimentos Técnicos', 'Despesa', 3
    UNION ALL SELECT 'D20205', 'D202', '2.02.005', 'Depreciação de Equipamentos', 'Despesa', 3
    UNION ALL SELECT 'D203', 'D2', '2.03', 'Tecnologia da Informação', 'Despesa', 2
    UNION ALL SELECT 'D20301', 'D203', '2.03.001', 'Licenças e Assinaturas de Software', 'Despesa', 3
    UNION ALL SELECT 'D20302', 'D203', '2.03.002', 'Nuvem, Hospedagem e Infraestrutura', 'Despesa', 3
    UNION ALL SELECT 'D20303', 'D203', '2.03.003', 'Integrações, APIs e Comunicação', 'Despesa', 3
    UNION ALL SELECT 'D20304', 'D203', '2.03.004', 'Desenvolvimento, Suporte e Consultoria de TI', 'Despesa', 3
    UNION ALL SELECT 'D20305', 'D203', '2.03.005', 'Segurança da Informação e LGPD', 'Despesa', 3
    UNION ALL SELECT 'D204', 'D2', '2.04', 'Pessoas e Encargos', 'Despesa', 2
    UNION ALL SELECT 'D20401', 'D204', '2.04.001', 'Salários e Pró-Labore', 'Despesa', 3
    UNION ALL SELECT 'D20402', 'D204', '2.04.002', 'Encargos Trabalhistas e Benefícios', 'Despesa', 3
    UNION ALL SELECT 'D20403', 'D204', '2.04.003', 'Treinamentos e Desenvolvimento Profissional', 'Despesa', 3
    UNION ALL SELECT 'D205', 'D2', '2.05', 'Despesas Administrativas', 'Despesa', 2
    UNION ALL SELECT 'D20501', 'D205', '2.05.001', 'Aluguel, Condomínio e IPTU', 'Despesa', 3
    UNION ALL SELECT 'D20502', 'D205', '2.05.002', 'Energia, Água, Gases Medicinais e Utilidades', 'Despesa', 3
    UNION ALL SELECT 'D20503', 'D205', '2.05.003', 'Telefonia, Internet e Correios', 'Despesa', 3
    UNION ALL SELECT 'D20504', 'D205', '2.05.004', 'Serviços Contábeis, Jurídicos e Administrativos', 'Despesa', 3
    UNION ALL SELECT 'D20505', 'D205', '2.05.005', 'Seguros, Limpeza e Segurança Patrimonial', 'Despesa', 3
    UNION ALL SELECT 'D206', 'D2', '2.06', 'Comercial e Marketing', 'Despesa', 2
    UNION ALL SELECT 'D20601', 'D206', '2.06.001', 'Marketing e Publicidade', 'Despesa', 3
    UNION ALL SELECT 'D20602', 'D206', '2.06.002', 'Comissões Comerciais', 'Despesa', 3
    UNION ALL SELECT 'D20603', 'D206', '2.06.003', 'Eventos, Congressos e Relacionamento', 'Despesa', 3
    UNION ALL SELECT 'D20604', 'D206', '2.06.004', 'Viagens, RDV e Representação', 'Despesa', 3
    UNION ALL SELECT 'D207', 'D2', '2.07', 'Tributos e Despesas Financeiras', 'Despesa', 2
    UNION ALL SELECT 'D20701', 'D207', '2.07.001', 'Impostos sobre Serviços e Vendas', 'Despesa', 3
    UNION ALL SELECT 'D20702', 'D207', '2.07.002', 'Tarifas Bancárias e Meios de Pagamento', 'Despesa', 3
    UNION ALL SELECT 'D20703', 'D207', '2.07.003', 'Juros, Multas e Encargos Financeiros', 'Despesa', 3
    UNION ALL SELECT 'D208', 'D2', '2.08', 'Outras Despesas Operacionais', 'Despesa', 2
    UNION ALL SELECT 'D20801', 'D208', '2.08.001', 'Perdas, Ajustes e Baixas', 'Despesa', 3
    UNION ALL SELECT 'D20802', 'D208', '2.08.002', 'Despesas Não Recorrentes', 'Despesa', 3
) m
WHERE t.`status` = 'active'
  AND t.`master_user_id` IS NOT NULL
  AND t.`slug` <> 'imagiflow-saas-admin';

-- Reconstrói a hierarquia sem modificar contas manuais do tenant.
UPDATE `plano_contas` filha
INNER JOIN (
    SELECT 'R101' AS filha_modelo, 'R1' AS pai_modelo
    UNION ALL SELECT 'R10101', 'R101' UNION ALL SELECT 'R10102', 'R101'
    UNION ALL SELECT 'R10103', 'R101' UNION ALL SELECT 'R10104', 'R101'
    UNION ALL SELECT 'R102', 'R1' UNION ALL SELECT 'R10201', 'R102'
    UNION ALL SELECT 'R10202', 'R102' UNION ALL SELECT 'R10203', 'R102'
    UNION ALL SELECT 'R10204', 'R102' UNION ALL SELECT 'R103', 'R1'
    UNION ALL SELECT 'R10301', 'R103' UNION ALL SELECT 'R10302', 'R103'
    UNION ALL SELECT 'R10303', 'R103' UNION ALL SELECT 'R104', 'R1'
    UNION ALL SELECT 'R10401', 'R104' UNION ALL SELECT 'R10402', 'R104'
    UNION ALL SELECT 'D201', 'D2' UNION ALL SELECT 'D20101', 'D201'
    UNION ALL SELECT 'D20102', 'D201' UNION ALL SELECT 'D20103', 'D201'
    UNION ALL SELECT 'D20104', 'D201' UNION ALL SELECT 'D20105', 'D201'
    UNION ALL SELECT 'D202', 'D2' UNION ALL SELECT 'D20201', 'D202'
    UNION ALL SELECT 'D20202', 'D202' UNION ALL SELECT 'D20203', 'D202'
    UNION ALL SELECT 'D20204', 'D202' UNION ALL SELECT 'D20205', 'D202'
    UNION ALL SELECT 'D203', 'D2' UNION ALL SELECT 'D20301', 'D203'
    UNION ALL SELECT 'D20302', 'D203' UNION ALL SELECT 'D20303', 'D203'
    UNION ALL SELECT 'D20304', 'D203' UNION ALL SELECT 'D20305', 'D203'
    UNION ALL SELECT 'D204', 'D2' UNION ALL SELECT 'D20401', 'D204'
    UNION ALL SELECT 'D20402', 'D204' UNION ALL SELECT 'D20403', 'D204'
    UNION ALL SELECT 'D205', 'D2' UNION ALL SELECT 'D20501', 'D205'
    UNION ALL SELECT 'D20502', 'D205' UNION ALL SELECT 'D20503', 'D205'
    UNION ALL SELECT 'D20504', 'D205' UNION ALL SELECT 'D20505', 'D205'
    UNION ALL SELECT 'D206', 'D2' UNION ALL SELECT 'D20601', 'D206'
    UNION ALL SELECT 'D20602', 'D206' UNION ALL SELECT 'D20603', 'D206'
    UNION ALL SELECT 'D20604', 'D206' UNION ALL SELECT 'D207', 'D2'
    UNION ALL SELECT 'D20701', 'D207' UNION ALL SELECT 'D20702', 'D207'
    UNION ALL SELECT 'D20703', 'D207' UNION ALL SELECT 'D208', 'D2'
    UNION ALL SELECT 'D20801', 'D208' UNION ALL SELECT 'D20802', 'D208'
) relacao ON relacao.filha_modelo = filha.modelo_padrao_codigo
INNER JOIN `plano_contas` pai
    ON pai.tenant_id = filha.tenant_id
   AND pai.modelo_padrao_codigo = relacao.pai_modelo
SET filha.conta_pai_id = pai.id,
    filha.nivel = pai.nivel + 1
WHERE filha.modelo_padrao_codigo IS NOT NULL;

-- VALIDAÇÃO
SELECT tenant_id, tipo, COUNT(*) AS total_contas_padrao
FROM plano_contas
WHERE modelo_padrao_codigo IS NOT NULL
GROUP BY tenant_id, tipo
ORDER BY tenant_id, tipo;

SELECT tenant_id, codigo, nome, tipo, nivel, conta_pai_id
FROM plano_contas
WHERE modelo_padrao_codigo IS NOT NULL
ORDER BY tenant_id, codigo;

-- Não há rollback automático: este script cria dados operacionais que podem
-- ser editados pelos tenants. Caso seja necessário desfazer, faça backup e
-- remova manualmente apenas registros com modelo_padrao_codigo preenchido.
