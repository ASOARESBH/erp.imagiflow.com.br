# Auditoria Inicial do Esquema e Plano de Reconciliação

**Projeto de destino:** ERP Imagiflow
**Base auditada:** commit `0a5ef7077053b8b904495b91a06bbe692e1dccb0` do ERP InLaudo
**Escopo desta etapa:** repositório versionado e migrações disponíveis localmente. A estrutura efetiva do banco de produção ainda precisa ser comparada por meio de uma exportação exclusivamente estrutural.

> As migrações presentes no repositório registram a evolução conhecida do banco, mas não são uma fotografia confiável e completa do ambiente de produção. Há objetos criados antes do primeiro arquivo disponível, alterações manuais possíveis e trechos incompatíveis com o padrão alvo. Por isso, a exportação sem dados é a fonte necessária para estabelecer a linha de base.

## Arquitetura identificada

A aplicação é um projeto PHP com autoload PSR-4, padrão MVC próprio e conexão PDO configurada em `config/database.php`. O `composer.json` requer PHP 8 ou superior e não declara um framework de migração. O repositório contém 54 controllers, 78 models, 16 services, 6 middlewares e 194 views PHP. A execução das migrações é manual: a busca no código não encontrou um runner versionado para aplicar/registrar automaticamente os arquivos SQL.

| Área | Evidência no repositório | Implicação para a migração |
|---|---|---|
| Aplicação | `app/`, `routes/web.php`, `config/database.php` | A implantação deve preservar MVC, PDO e a configuração por ambiente. |
| Banco | `database/migrations/` | É preciso consolidar um baseline real antes de criar as migrações multitenant. |
| Runtime | PHP 8+, MySQL/MariaDB 5.7 | O SQL novo precisa evitar recursos de MySQL 8, CTEs e window functions. |
| Hospedagem | Ambiente compartilhado previsto | Procedures, triggers e events não devem ser usados como mecanismo de migração. |
| Identidade atual | 161 arquivos rastreados mencionam `InLaudo`/`inlaudo`; 34 mencionam `erp.inlaudo.com.br` | A troca de marca e domínio será feita por inventário contextual, não por substituição cega. |

## Cobertura das migrações existentes

Foram localizados **75 arquivos SQL**, com aproximadamente **9.946 linhas**. Eles contêm referências a 107 tabelas criadas e 139 comandos `CREATE TABLE`, além de alterações posteriores. Esses números indicam uma evolução importante do esquema, porém não eliminam a necessidade de confrontar o resultado com a produção.

| Elemento encontrado | Quantidade/estado | Leitura técnica |
|---|---:|---|
| `CREATE TABLE` | 139 ocorrências | Inclui recriações condicionais e tabelas auxiliares. |
| `ALTER TABLE` | 239 ocorrências | O esquema depende fortemente da ordem e do estado prévio. |
| Chaves estrangeiras | 92 ocorrências | A reconciliação deve validar tipos, índices e ordem de criação. |
| Views | Nenhuma criação localizada | Deve ser confirmado no export da produção, pois podem existir objetos manuais. |
| Rotinas persistentes | 4 arquivos definem procedures; um quinto arquivo contém apenas comentário sobre a proibição | Devem ser catalogadas no export, mas não replicadas automaticamente. |
| Índices explícitos | 22 comandos `CREATE INDEX` | Os demais índices podem estar nas próprias definições de tabela. |

## Riscos de compatibilidade identificados

A base de migrações mistura padrões distintos. Não houve alteração desses arquivos nesta etapa: eles serão preservados como histórico e as correções serão aplicadas apenas em migrações novas, após a comparação com a produção.

| Padrão encontrado | Ocorrências | Risco para o ambiente alvo |
|---|---:|---|
| `utf8mb4` | 95 linhas | Diverge do padrão definido para o ambiente atual, `utf8` e `utf8_unicode_ci`. |
| `ADD COLUMN IF NOT EXISTS` | 154 linhas | Sintaxe não compatível com MySQL 5.7. |
| `CREATE INDEX IF NOT EXISTS` | 21 linhas | Sintaxe não compatível com MySQL 5.7. |
| Consultas a `information_schema` | 97 linhas | Usadas para tornar scripts condicionais; não devem compor migrações novas no ambiente compartilhado. |
| Procedures para executar DDL condicional | 4 arquivos | Não devem ser mantidas como dependência de instalação. |

## Estratégia adotada para a linha de base

Foi criado o utilitário `tools/exportar_esquema_migracao.sh`. Ele executa `mysqldump` com `--no-data`, preservando apenas DDL de tabelas, colunas, chaves primárias, índices, chaves estrangeiras, views, routines, triggers e events. O resultado é compactado e acompanhado de manifesto e checksum SHA-256. Nenhum `INSERT`, registro, anexo, upload, `.env` ou credencial é incluído.

Quando existirem cláusulas `DEFINER` em objetos SQL, o utilitário as substitui por `CURRENT_USER` antes da compactação. Isso evita revelar usuário/host do banco no arquivo de auditoria. O dump continua sendo um artefato de análise e **não deve ser importado diretamente em produção**.

O utilitário foi validado localmente com uma simulação que contém tabela, índice e procedure. A validação confirmou sintaxe Bash, ausência de `INSERT` de dados e anonimização de `DEFINER`.

## Próximas etapas após o recebimento do esquema

| Ordem | Atividade | Resultado esperado |
|---:|---|---|
| 1 | Descompactar e catalogar o DDL de produção. | Inventário de tabelas, colunas, índices, FKs, views e rotinas. |
| 2 | Comparar o inventário com `database/migrations/`. | Matriz de divergências, com origem e impacto. |
| 3 | Classificar cada tabela como global, configuracional ou pertencente a tenant. | Mapa de isolamento para a arquitetura `tenant_id`. |
| 4 | Definir a estratégia de usuários e relações de tenant. | Decisão documentada entre `users.tenant_id` e `user_tenants`. |
| 5 | Gerar migrações incrementais compatíveis com MySQL/MariaDB 5.7. | SQL revisável, com pré-validação, validação e rollback. |
| 6 | Auditar queries e pontos de acesso por módulo. | Lista priorizada de riscos de vazamento entre tenants. |

## Artefatos desta etapa

| Caminho | Finalidade |
|---|---|
| `tools/exportar_esquema_migracao.sh` | Gera exportação estrutural, compactada e anonimizada. |
| `docs/database/exportacao-esquema-producao.md` | Instruções para executar, conferir e compartilhar a exportação. |
| `docs/database/auditoria-inicial-esquema.md` | Este relatório e a estratégia de reconciliação. |

## Observação sobre o novo repositório

Uma cópia local de trabalho foi preparada em `/home/ubuntu/erp_imagiflow`, apontada para o futuro repositório `ASOARESBH/erp.imagiflow.com.br` como `origin` e para o repositório InLaudo como `upstream`. A criação remota não foi concluída porque a credencial atual do GitHub não possui permissão para criar repositórios na organização. Nenhuma modificação foi enviada ao repositório de origem.
