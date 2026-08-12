# Arquitetura Multitenant Inicial — ERP Imagiflow

## Decisão adotada

A primeira etapa utilizará **uma única base de dados com isolamento por `tenant_id`**. O tenant será descoberto pelo host HTTP antes de qualquer rota, definido em um `TenantContext` central e nunca será aceito por `GET`, `POST`, JSON ou outro parâmetro enviado pelo cliente.

A tabela `users` permanece global. A associação de acesso será representada por `user_tenants`, o que permite que um usuário seja associado a mais de um tenant no futuro sem duplicar credenciais. O login continuará com a mesma tela e fluxo; a autenticação selecionará apenas o vínculo do usuário com o tenant identificado pelo domínio.

| Elemento | Decisão | Justificativa |
|---|---|---|
| Detecção do tenant | `HTTP_HOST` por middleware global | Impede que a origem do tenant seja controlada pelo frontend. |
| Contexto por requisição | `App\Core\TenantContext` | Centraliza `tenant_id` e falha se o contexto estiver ausente. |
| Usuários | Globais, com pivô `user_tenants` | Evita duplicar conta, senha e 2FA; permite vínculos futuros. |
| Dados de negócio | `tenant_id` direto e índice composto | Permite isolamento simples e consultas eficientes em uma única base. |
| Tenant inicial | Imagiflow, domínio `erp.imagiflow.com.br` | Mantém o acesso transparente sem tela de escolha. |
| Dados já existentes | Associados ao tenant inicial via migration | Preserva a operação atual sem copiar dados. |

## Classificação inicial das tabelas

A classificação abaixo serve para a migration de fundação. Ela não substitui a revisão por módulo, que será feita antes de cada alteração de controller/model.

| Categoria | Tabelas | Regra inicial |
|---|---|---|
| Globais | `tenants`, `users`, `user_tenants`, `especialidades`, tabelas `cnes_*`, `cnes_dom_*`, tabelas `_cnes_*`, `manual_artigos`, `manual_categorias` | Não recebem `tenant_id` nesta etapa. |
| Configuração ou dados de tenant | `empresa_config`, `config_nfs`, `configuracoes_financeiras`, `layout_exames`, `notificacao_config_alertas`, `hub_ia_banco_config`, `hub_ia_whatsapp_config` | Recebem `tenant_id`; unicidades globais deverão ser revisadas para chaves compostas. |
| Dados operacionais e relacionamentos | Demais tabelas de clientes, contratos, financeiro, estoque, CRM, marketing, profissionais, apuração, RDV, portal, logs e integrações | Recebem `tenant_id`, preenchido com o tenant inicial e indexado. |

## Regras de implementação

A migration de fundação deve ser aplicada apenas no banco de destino do Imagiflow, depois de restaurar o esquema e a cópia de dados autorizada. Ela cria a tabela `tenants`, registra o tenant inicial, cria `user_tenants` e adiciona/retropreenche `tenant_id` nas tabelas classificadas como pertencentes a tenant.

Como o ambiente é compatível com MySQL/MariaDB 5.7, os comandos são explícitos, sem `ADD COLUMN IF NOT EXISTS`, `CREATE INDEX IF NOT EXISTS`, `information_schema`, procedures, triggers ou events. Antes da execução, as verificações `SHOW COLUMNS` e `SHOW INDEX` definidas na própria migration devem ser realizadas manualmente no phpMyAdmin.

> A fundação de banco não torna automaticamente todas as queries legadas seguras. Depois de habilitar o contexto, os models e serviços precisam receber escopo por tenant de forma incremental, com prioridade para login, clientes, portal, financeiro, CRM e APIs.
