# Multi-tenant e Banco de Dados — Detalhe

Documentos-fonte: `docs/database/arquitetura-multitenant.md` (decisão original) e
`database/migrations/2026-08-12_multitenant_foundation.sql` (implementação real — 65KB,
é a fonte mais confiável e atualizada, use-a para conferir se uma tabela específica tem
`tenant_id` ou não). Este arquivo resume os dois para consulta rápida.

## Modelo adotado

Uma única base de dados, isolamento por `tenant_id` em cada tabela de negócio. O tenant é
descoberto pelo `HTTP_HOST` da requisição, **nunca** por parâmetro enviado pelo cliente
(GET/POST/JSON/header custom). `App\Core\TenantContext` é o único lugar que guarda o
tenant da requisição atual, e lança `LogicException` se for consultado sem ter sido
definido — isso é intencional (fail-closed), não um bug.

`users` continua global (não tem `tenant_id`); o vínculo usuário↔tenant vive em
`user_tenants` (colunas `user_id`, `tenant_id`, `role`, `status`, `is_default`), permitindo
no futuro um usuário pertencer a mais de um tenant sem duplicar credenciais/2FA. Hoje só
existe um tenant real, `Imagiflow` (id `1`, slug `imagiflow`, domínio
`erp.imagiflow.com.br`) — os dados históricos foram todos migrados para
`tenant_id = 1` como valor padrão.

Em execução via CLI (cron), não há `HTTP_HOST`; o tenant vem obrigatoriamente de
`TENANT_DEFAULT_SLUG` no `.env`/ambiente do servidor — nunca de argumento de linha de
comando (ver `IdentifyTenantMiddleware::handle()`).

## Tabelas GLOBAIS (sem `tenant_id`) — catálogo/infra, não são dado de negócio de um tenant

```
tenants, users, user_tenants
especialidades
manual_artigos, manual_categorias
cnes_* / _cnes_* (toda a família de tabelas do CNES/DataSUS — estabelecimentos,
  equipamentos, profissionais, domínios de apoio)
tabelas de CBO (Classificação Brasileira de Ocupações — carga feita pela migration
  2026-05-01_cbo_completo.sql, 135KB, é um catálogo nacional público)
```

Se você não encontrar uma tabela na lista de tenant-scoped abaixo, ela é candidata a
global — mas **confirme sempre com `SHOW COLUMNS FROM <tabela> LIKE 'tenant_id';` ou
grep na migration de fundação** antes de assumir, porque a classificação pode evoluir.

## Tabelas com `tenant_id` (escopo obrigatório por tenant) — lista extraída da migration real

Toda tabela abaixo recebeu `tenant_id INT(11) UNSIGNED NOT NULL DEFAULT 1` +
índice `idx_<tabela>_tenant_id` na migration `2026-08-12_multitenant_foundation.sql`.
**Toda query nova (Model) contra qualquer uma destas tabelas deve filtrar por
`tenant_id = TenantContext::id()`:**

```
apuracao_itens, apuracoes, audit_logs
clientes, clientes_anexos, clientes_contatos
colaboradores, colaboradores_anexos, colaboradores_comissoes
config_nfs, configuracoes_financeiras
contas_bancarias, contas_movimentacoes, contas_pagar, contas_pagar_anexos,
  contas_receber, contas_receber_anexos
contrato_exames, contrato_modalidades, contratos, contratos_anexos
crm_anexos, crm_interacoes, crm_leads, crm_oportunidade_modalidades, crm_oportunidades,
  crm_proposta_aceite, crm_proposta_historico, crm_proposta_itens, crm_propostas,
  crm_transferencias
dispositivos_controlid, dispositivos_controlid_leituras, dispositivos_controlid_sync_log
email_alertas, email_alertas_log
empresa_config
equipamentos_cliente
est_movimentacoes, est_pedido_seq, est_pedidos_compra, est_pedidos_compra_itens,
  est_pedidos_venda, est_pedidos_venda_itens
fornecedores
historico_importacoes_ofx
hub_ia_agente_permissoes, hub_ia_agentes, hub_ia_banco_config, hub_ia_conectores,
  hub_ia_conhecimento_chunks, hub_ia_conhecimento_documentos, hub_ia_historico,
  hub_ia_logs, hub_ia_prompts, hub_ia_whatsapp_config
integracoes, integracoes_logs
layout_exames
manual_historico
manut_ordens_servico, manut_os_historico, manut_os_seq, manut_os_trocas
marketing_campanhas, marketing_disparadores, marketing_envios, marketing_interacoes_crm
medico_crms, medico_exames, medicos
mkt_campanha_contatos, mkt_campanhas
movimentacoes_bancarias
notas_fiscais, notas_fiscais_anexos, notas_fiscais_importacoes
notificacao_config_alertas, notificacoes
password_reset_tokens
plano_contas
portal_clientes, portal_clientes_tokens
produto_codigo_seq, produto_comissoes, produto_componentes, produto_historico_precos,
  produto_lotes, produto_movimentacoes, produtos,
  produtos_bkp_20260604, produtos_bkp_deprec_20260604 (tabelas de backup, não usar em código novo)
rdv_aprovacoes, rdv_categorias, rdv_despesas, rdv_formas_pagamento, rdv_historico,
  rdv_ocr_logs, rdv_rota_clientes, rdv_rotas, rdv_seq, rdv_viagens
security_two_factor_logs
tabela_exames, tabela_exames_tags
```

Note que `marketing_campanhas`/`marketing_disparadores`/`marketing_envios` e
`mkt_campanhas`/`mkt_campanha_contatos` **coexistem** — parecem ser duas gerações da
mesma funcionalidade de marketing. Confirme qual conjunto o `Controller`/`Model` ativo
usa (`MarketingCampanhasController`/`MarketingCampanha.php` model) antes de assumir que
ambos estão em uso — é provável que um dos dois seja legado.

## Convivência com `usuario_id` legado

Código mais antigo do sistema (pré-multi-tenant) filtra dados por `usuario_id` (o usuário
logado, não o tenant). Isso ainda funciona e convive com `tenant_id` durante a migração
incremental. Ao tocar em Model antigo, prefira **adicionar** filtro por `tenant_id` em
paralelo ao que já existe, em vez de remover o filtro por `usuario_id` sem entender o
impacto — regra de ouro 6 (nunca quebrar o que já funciona).

## SQL / Migrations — MySQL 5.7

Este projeto roda em **MySQL 5.7 puro** (confirmado em produção via erro real `#1064` ao
tentar `ADD COLUMN IF NOT EXISTS`, que é sintaxe MariaDB/MySQL 8.0+, inexistente em 5.7).
As regras completas de sintaxe compatível (idempotência via `information_schema` +
`PREPARE`/`EXECUTE`, sem CTE/window function, `CHANGE COLUMN` em vez de `RENAME COLUMN`,
convenção de nome de arquivo/cabeçalho) estão no skill separado
**`.claude/skills/mysql57-migrations/SKILL.md`** — leia-o sempre que for escrever ou
revisar um `.sql` novo. Não duplicar essas regras aqui; este arquivo cobre só a parte
multi-tenant.

Lembretes operacionais específicos deste projeto:
- Migrations são arquivos `.sql` soltos em `database/migrations/`, aplicados
  **manualmente** (phpMyAdmin ou `mysql` CLI) — não existe runner/CLI de migration.
- Nomeação: `YYYY-MM-DD_descricao-curta.sql`.
- Schema de referência completo (dump): `database/schema/2026-08-12_imagiflow_baseline.sql`
  (~240KB) — use para conferir a estrutura real de uma tabela sem depender só do que os
  Models assumem.
- `database/seeders/` tem apenas seeds pontuais (`AdminUserSeeder.sql`,
  `inlaud99_saas_inlaudo.sql`) — não é um sistema de seeding automatizado.
- Script utilitário `tools/exportar_esquema_migracao.sh` — export de esquema para apoio a
  migração/deploy (útil ao preparar um novo baseline de schema).

## Regra de ouro do banco (repetindo por ênfase — ver seção 2 do `SKILL.md` principal)

Nunca `DROP COLUMN`/renomear coluna ou tabela existente. Só `CREATE TABLE` e
`ADD COLUMN`. Remoção de dado é sempre soft delete. Este é o princípio arquitetural mais
citado em toda a documentação interna do projeto — trate como inviolável mesmo sob
pressão de "limpar" schema legado.
