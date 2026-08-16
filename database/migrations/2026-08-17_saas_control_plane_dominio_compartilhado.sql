-- Migração: painel SaaS no domínio ERP compartilhado
-- Data: 2026-08-17 | Sistema: ERP IMAGINIFLOW
-- Compatível com MySQL 5.7 / HostGator.
-- Esta migration não altera tenants operacionais e não cria DNS.

-- O tenant de controle deixa de reservar painel.imagiflow.com.br.
-- O acesso passa a ocorrer por https://erp.imagiflow.com.br/painel.
UPDATE tenants
SET domain = 'saas-control.internal',
    subdomain = NULL,
    updated_at = NOW()
WHERE slug = 'imagiflow-saas-admin'
  AND domain = 'painel.imagiflow.com.br';

-- VALIDAÇÃO
SELECT id, name, slug, domain, subdomain, status
FROM tenants
WHERE slug = 'imagiflow-saas-admin';

-- ROLLBACK MANUAL (execute somente se ainda desejar restaurar o subdomínio legado):
-- UPDATE tenants
-- SET domain = 'painel.imagiflow.com.br', subdomain = NULL, updated_at = NOW()
-- WHERE slug = 'imagiflow-saas-admin';
