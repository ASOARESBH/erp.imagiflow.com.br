# Implantação Controlada do Banco Imagiflow no HostGator

## Objetivo e limites

Este roteiro cria o banco novo `inlaud99_saasimagiflow` com a **mesma estrutura** identificada no ERP de origem e, em seguida, aplica a fundação multitenant. O baseline não contém registros, anexos, uploads, senhas nem tokens. A migration multitenant cria o tenant Imagiflow, os vínculos de usuários e as colunas/índices `tenant_id`.

> **Nunca execute estes arquivos no banco de produção original.** Antes de qualquer importação, confirme no phpMyAdmin que o banco selecionado é `inlaud99_saasimagiflow`.

| Arquivo | Finalidade | Ordem |
|---|---|---:|
| `database/schema/2026-08-12_imagiflow_baseline.sql` | Cria as 122 tabelas e a estrutura histórica sem dados. | 1 |
| `database/migrations/2026-08-12_multitenant_foundation.sql` | Cria `tenants`, `user_tenants`, o tenant inicial e o isolamento por `tenant_id`. | 2 |

## Pré-requisitos no cPanel

Crie o banco `inlaud99_saasimagiflow`, um usuário exclusivo para ele e conceda **ALL PRIVILEGES** apenas nesse banco. Não reutilize a base de produção. No arquivo `.env` do novo site, configure o banco novo e o domínio novo. As credenciais não devem ser adicionadas ao Git.

```ini
APP_ENV=prod
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=inlaud99_saasimagiflow
DB_USERNAME=USUARIO_DO_BANCO_NOVO
DB_PASSWORD=SENHA_DO_BANCO_NOVO
DB_CHARSET=utf8mb4
APP_URL=https://erp.imagiflow.com.br

# Necessário apenas para cron/CLI; não use em requisições web.
TENANT_DEFAULT_SLUG=imagiflow
```

## Importação do baseline

No phpMyAdmin, selecione `inlaud99_saasimagiflow`, abra **Importar**, envie `database/schema/2026-08-12_imagiflow_baseline.sql` e execute. Esse passo pode demorar por causa da quantidade de tabelas e chaves estrangeiras, mesmo sem registros.

Depois confirme a criação estrutural:

```sql
SHOW TABLES;
SELECT COUNT(*) AS total_tabelas
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'inlaud99_saasimagiflow';
```

O resultado esperado é **122 tabelas antes da migration multitenant**. A consulta a `information_schema` acima é somente uma verificação manual no phpMyAdmin; ela não faz parte da migration nem do código da aplicação.

## Aplicação da foundation multitenant

Antes de importar `2026-08-12_multitenant_foundation.sql`, execute as verificações comentadas no início do arquivo. A migration foi gerada para uma base recém-criada. Não a reaplique se alguma coluna `tenant_id` ou índice `idx_*_tenant_id` já tiver sido criado, pois MySQL 5.7 não oferece `IF NOT EXISTS` para esses comandos.

Importe a migration no mesmo banco novo. Ao final, execute:

```sql
SELECT id, name, slug, domain, status
FROM tenants;

SELECT tenant_id, COUNT(*) AS usuarios_vinculados
FROM user_tenants
GROUP BY tenant_id;

SHOW COLUMNS FROM clientes LIKE 'tenant_id';
SHOW INDEX FROM clientes WHERE Key_name = 'idx_clientes_tenant_id';
SHOW COLUMNS FROM portal_clientes LIKE 'tenant_id';
SHOW COLUMNS FROM password_reset_tokens LIKE 'tenant_id';
```

A migration cria o tenant inicial com os valores abaixo:

| Campo | Valor |
|---|---|
| `id` | `1` |
| `name` | `Imagiflow` |
| `slug` | `imagiflow` |
| `domain` | `erp.imagiflow.com.br` |
| `status` | `active` |

## Publicação da aplicação

Publique o código do Imagiflow no novo diretório do domínio, mantendo `public/` como raiz pública. Depois de configurar o domínio/subdomínio e o certificado HTTPS, acesse `https://erp.imagiflow.com.br/login`.

O middleware identifica o tenant pelo `HTTP_HOST`. Se o domínio não coincidir exatamente com o valor em `tenants.domain`, a aplicação responderá que o ambiente não foi encontrado. Não adicione `tenant_id` em URL, formulário ou payload para contornar esse comportamento.

## Rollback

A migration possui um rollback comentado no final. Ele deve ser usado somente no banco novo e somente depois de validar que não há dependências posteriores. Em caso de falha durante a importação do baseline ou da foundation, a alternativa mais segura é excluir o banco novo e recriá-lo, pois ele ainda não possui dados operacionais.

## Próxima etapa obrigatória

A fundação não substitui a revisão dos models e serviços restantes. Os primeiros módulos já protegidos são autenticação, usuários, portal, recuperação de senha e clientes. Antes de colocar um segundo tenant em operação, cada módulo de CRM, financeiro, estoque, contratos, apuração, RDV e APIs deverá receber filtros e inserções de `tenant_id` revisados por fluxo.
