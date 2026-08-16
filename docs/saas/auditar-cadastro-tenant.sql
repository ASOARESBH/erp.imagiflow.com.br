-- Auditoria de cadastro de empresas SaaS
-- Banco alvo: inlaud99_saasimagiflow | compatível com MySQL/MariaDB 5.7

-- 1. Empresas-tenants, plano e usuário master vinculados.
SELECT
  t.id,
  t.nome_fantasia,
  t.razao_social,
  t.cnpj,
  t.slug,
  t.status,
  p.nome AS plano,
  u.name AS master_nome,
  u.email AS master_email,
  t.created_at
FROM tenants t
LEFT JOIN planos p ON p.id = t.plano_id
LEFT JOIN users u ON u.id = t.master_user_id
WHERE t.slug <> 'imagiflow-saas-admin'
ORDER BY t.created_at DESC, t.id DESC;

-- 2. Vínculos de usuário por empresa. Um cadastro concluído deve mostrar
-- o usuário master com role superadmin e status active.
SELECT
  t.id AS tenant_id,
  t.nome_fantasia,
  u.id AS user_id,
  u.email,
  ut.role,
  ut.status,
  ut.is_default
FROM tenants t
INNER JOIN user_tenants ut ON ut.tenant_id = t.id
INNER JOIN users u ON u.id = ut.user_id
WHERE t.slug <> 'imagiflow-saas-admin'
ORDER BY t.id DESC, u.email ASC;

-- 3. Planos disponíveis. Sem plano ativo, o cadastro será bloqueado com mensagem.
SELECT id, nome, slug, status, limite_usuarios, valor_mensal
FROM planos
ORDER BY nome ASC;

-- 4. Verificação pontual pelo CNPJ da empresa em tentativa de cadastro.
-- Substitua somente os dígitos do CNPJ abaixo antes de executar.
SELECT id, nome_fantasia, cnpj, slug, status, created_at
FROM tenants
WHERE cnpj = '00000000000000';
