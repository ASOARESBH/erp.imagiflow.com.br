# Painel SaaS de Administração — ERP IMAGINIFLOW

## Finalidade

O painel SaaS é o **control-plane** da plataforma. Ele permite que a equipe Imagiflow cadastre empresas-clientes, associe planos e módulos, envie o convite do usuário master, suspenda empresas e realize suporte por impersonação auditada. A operação normal de cada empresa continua isolada pelo vínculo ativo do usuário. Nos hosts dedicados legados, o tenant é resolvido pelo host; em `erp.imagiflow.com.br`, o tenant é restaurado apenas após validar o vínculo ativo `user_tenants` no banco.

> O painel SaaS não substitui a administração interna de cada empresa. O usuário master de uma empresa continua sendo `superadmin` apenas no seu próprio tenant.

| Camada | Responsabilidade | Regra de segurança |
|---|---|---|
| Tenant de controle | Acessa `/painel/*` | Deve ser o tenant cujo ID está em `SAAS_CONTROL_TENANT_ID`. |
| `saas_owner` | Administra plataforma, empresas e planos | Só pode ser vinculado ao tenant de controle. |
| `superadmin` | Administra uma empresa-cliente | Não recebe permissões SaaS. |
| RBAC | Define ações do papel do usuário | É checado por middleware. |
| PlanGate | Define módulos contratados pelo tenant | Complementa RBAC; não o substitui. |

## Ativação no HostGator

A ativação deve ocorrer em ordem. Não é necessário criar subdomínio nem DNS adicional. O painel é acessado no mesmo host da aplicação, em `https://erp.imagiflow.com.br/painel`, depois do login do superadmin.

Depois, no phpMyAdmin do banco `inlaud99_saasimagiflow`, aplique `database/migrations/2026-08-16_saas_admin_planos_empresas.sql`. A migration foi escrita para MySQL/MariaDB 5.7 e não contém remoção ou renomeação de estruturas. Como o ambiente não permite idempotência automática por consultas estruturais dentro da migration, se ela estiver sendo reaplicada em um banco onde alguma coluna de `tenants` já exista, remova **somente a linha daquela coluna** do bloco `ALTER TABLE` antes de executar novamente. Não remova tabelas, índices ou dados existentes.

Após a execução, copie o ID retornado pelo seguinte comando:

```sql
SELECT id, name, slug, domain
FROM tenants
WHERE slug = 'imagiflow-saas-admin';
```

No arquivo `.env` do HostGator, configure:

```ini
SAAS_CONTROL_TENANT_ID=ID_RETORNADO_ACIMA
SAAS_SHARED_HOST=erp.imagiflow.com.br
```

Em seguida, acesse `https://erp.imagiflow.com.br/login`, conclua o primeiro acesso do usuário seed e redefina sua senha imediatamente. O seed também marca o 2FA como habilitado; configure o e-mail de envio e conclua a validação de dois fatores antes de utilizar impersonação.

| Verificação após a migration | Resultado esperado |
|---|---|
| `SHOW TABLES LIKE 'planos'` | Tabela existente. |
| `SHOW TABLES LIKE 'plano_modulos'` | Tabela existente. |
| `SHOW TABLES LIKE 'tenant_impersonation_logs'` | Tabela existente. |
| `SELECT ... FROM tenants WHERE slug = 'imagiflow-saas-admin'` | Um único tenant de controle ativo. |
| `master@imagiflow.com.br` | Vínculo `saas_owner` somente no tenant de controle. |

## Rotina operacional

No tenant de controle, o menu **Painel SaaS** aparece somente para `saas_owner`. A página inicial resume empresas, planos e impersonações recentes. Em **Empresas**, o cadastro cria o tenant, o usuário master e o vínculo `superadmin` em uma transação. A senha não é exposta: o sistema gera um token com validade e envia um link de definição de senha pelo mecanismo de e-mail já utilizado na recuperação de acesso.

Ao cadastrar uma empresa, informe um plano ativo, o CNPJ e os dados de faturamento. O slug é somente um identificador técnico interno. Todos os usuários entram por `https://erp.imagiflow.com.br/login`; não é necessário criar DNS, vhost, subdomínio ou domínio próprio para cada empresa.

Em **Planos**, configure preço, limite de usuários e módulos. Para rotas protegidas por permissões mapeadas, o middleware verifica RBAC e `PlanGate`. Tenants sem plano continuam temporariamente compatíveis com os módulos legados; assim que um plano for atribuído, a camada de plano passa a restringir módulos configurados.

## Impersonação

A impersonação usa tokens de uso único, limitados a dez minutos, e mantém logs de início, término, IP, user agent e motivo. O handoff ocorre no mesmo domínio ERP e só materializa o tenant alvo após validar o token e o vínculo ativo do usuário master. Ao sair, a sessão `saas_owner` é restaurada no mesmo host.

Durante a impersonação, um banner fixo exibe **Modo de suporte ativo** e oferece o botão de saída. O middleware SaaS nega qualquer rota administrativa enquanto a impersonação estiver ativa. Não é permitido iniciar uma segunda impersonação antes de encerrar a atual.

> Nunca compartilhe links de impersonação, links de redefinição de senha ou valores do `.env`. Os tokens são segredos de uso único e os logs armazenam apenas seus hashes.

## Checklist de homologação

| Cenário | Resultado esperado |
|---|---|
| Login em `erp.imagiflow.com.br` como `saas_owner` | Menu Painel SaaS disponível. |
| Login de `saas_owner` em tenant de negócio | Painel SaaS indisponível. |
| Criação de empresa com CNPJ repetido | Operação negada sem gravar tenant parcial. |
| Criação de empresa sem plano ativo | Operação negada. |
| Convite sem SMTP disponível | Empresa criada; falha de entrega registrada em auditoria/log. |
| Suspensão de empresa | O próximo login do usuário vinculado é bloqueado pela validação de tenant ativo. |
| Impersonação | Banner visível, log aberto e saída restaura o painel de controle. |
| Acesso direto a módulo não contratado em rota protegida | Resposta 403. |

## Implantação de código

Envie o pacote de implantação para a raiz da aplicação no HostGator, extraia-o sem apagar os arquivos existentes e aplique as migrations antes de usar as rotas SaaS. Depois, atualize `.env` com `SAAS_SHARED_HOST=erp.imagiflow.com.br` e execute o checklist de homologação. Nenhum DNS adicional é necessário. O pacote não contém `.env`, dumps, anexos, uploads ou dependências `vendor`.
