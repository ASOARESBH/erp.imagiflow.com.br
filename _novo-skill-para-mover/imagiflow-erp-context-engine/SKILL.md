---
name: imagiflow-erp-context-engine
description: Motor de contexto do ERP Imagiflow (histórico "InLaudo") — sistema de gestão para prestadores de serviços de diagnóstico por imagem, em PHP 8 com MVC customizado (sem framework), SaaS multi-tenant, banco MySQL 5.7. Use SEMPRE que a tarefa envolver localizar, entender, alterar, revisar, planejar ou documentar qualquer coisa neste repositório (raiz local: C:\xampp\htdocs\dashboard\erp.imagiflow.com.br) — mesmo pedidos que pareçam pequenos: "corrige o formulário de cliente", "adiciona campo em contas a pagar", "cria uma rota nova", "onde fica a lógica de X", "por que o RBAC bloqueia Y", "cria um módulo novo", "cria uma migration". Ative também para perguntas de arquitetura ("como funciona o multi-tenant", "quem chama esse Model", "como funciona o formulário enterprise com abas"), para preparar commits/PRs, ou sempre que reler o repositório inteiro seria caro em tokens e tempo. Não é uma skill de ensino de programação: é um mapa e um protocolo de navegação para reduzir drasticamente a leitura de arquivos, evitar reanálise repetida do mesmo sistema, e aumentar a precisão das alterações. Deve ser combinada com a skill já existente ".claude/skills/mysql57-migrations" sempre que a tarefa tocar em SQL/migrations. Compatível com Claude Code, Manus AI e agentes similares.
---

# Motor de Contexto — ERP Imagiflow

Este skill existe para que qualquer IA (Claude, Manus, ou outra) consiga trabalhar neste
repositório **sem precisar escanear o sistema inteiro a cada tarefa**. Leia este arquivo
primeiro; ele te diz o que o sistema é, quais regras nunca podem ser violadas, onde fica
cada coisa, e quando abrir um dos arquivos de referência em `reference/` (que só devem ser
lidos quando a tarefa realmente precisar do detalhe daquele tema).

Se alguma informação aqui parecer desatualizada em relação ao código real, **o código e o
banco de dados são a fonte de verdade** — corrija o entendimento pela leitura pontual do
arquivo relevante, e, se fizer sentido, atualize este skill (ver seção final).

## 1. O que é este sistema

O **ERP Imagiflow** (nome anterior/legado nos arquivos internos: "InLaudo") é uma
plataforma de gestão para empresas prestadoras de serviços de **diagnóstico por imagem**
(clínicas/laboratórios de radiologia — CNES, médicos laudadores, contratos com
convênios/clientes, apuração de exames realizados, faturamento, financeiro, estoque de
equipamentos, CRM comercial, portal do cliente, etc.).

| Atributo | Valor |
|---|---|
| Linguagem/runtime | PHP 8.0+ puro (sem framework — MVC customizado próprio) |
| Banco de dados | **MySQL 5.7 real** (não MariaDB) — ver `.claude/skills/mysql57-migrations/SKILL.md` |
| Acesso a dados | PDO puro, sem ORM. `App\Core\Database` é um singleton de `PDO`. |
| Modelo de negócio | SaaS **multi-tenant** (ver seção 4) |
| Dependências externas | Mínimas — `vlucas/phpdotenv` é praticamente a única lib via Composer |
| Hospedagem de produção | cPanel/shared hosting (`erp.inlaudo.com.br` / `erp.imagiflow.com.br`) — **sem SSH root, sem runner automático de migration**, tudo é aplicado manualmente via phpMyAdmin |
| Ambiente local | XAMPP em `C:\xampp\htdocs\dashboard\erp.imagiflow.com.br` |
| Timezone da aplicação | `America/Sao_Paulo` (fixado em `bootstrap.php`) |

## 2. Regras de Ouro — NUNCA violar

Fonte completa e vinculante: `docs/REGRAS_DE_OURO.md` (documento de governança do projeto).
Resumo operacional:

1. **O banco é fonte de verdade e é imutável para trás.** Nunca `DROP COLUMN`, nunca
   `CHANGE`/renomear coluna ou tabela existente. Só é permitido `CREATE TABLE` e
   `ADD COLUMN`. Remoção de dados é sempre soft delete (`status = 'inativo'` ou similar).
2. **Auditoria nunca pode quebrar o fluxo principal.** Toda chamada a
   `AuditLogger::log()` já é internamente protegida por `try/catch` (ver
   `app/Core/Audit/AuditLogger.php`), mas ao adicionar lógica de auditoria nova em volta,
   mantenha esse princípio: falha de log não pode interromper a requisição.
3. **MVC purista, sem exceção:**
   - Controller: orquestra fluxo, valida entrada, checa permissão, chama Model, chama View. **Nunca** SQL, nunca HTML.
   - Model: regra de negócio + acesso a dados (SQL vive aqui). **Nunca** HTML/apresentação.
   - View: apenas apresentação (HTML + classes CSS + data-attributes). **Nunca** SQL, nunca lógica de negócio.
4. **RBAC obrigatório em toda ação sensível.** Todo endpoint que cria/edita/apaga/lista
   dados sensíveis precisa checar `Auth::can('permissao')` no Controller (backend) **e**
   esconder o botão/ação na View correspondente. Nunca confiar só na UI.
5. **Telas públicas usam layout público.** Login, esqueci-senha, reset de senha, aceite
   público de proposta e páginas do portal do cliente usam `public_header/footer.php` ou
   `portal_header/footer.php` — nunca `erp_header/footer.php` (que tem sidebar/menu
   interno de usuário autenticado).
6. **Nunca quebrar o que já funciona.** Mudanças incrementais, testadas, com justificativa
   técnica clara. Este sistema está em produção real com dados reais de clientes.

Checklist de PR (do próprio `REGRAS_DE_OURO.md`): banco não teve coluna removida/renomeada;
toda chamada de auditoria está em try/catch; Controller/Model/View respeitam seus papéis;
`Auth::can()` está presente nas ações sensíveis; tela pública usa layout público; nada
existente quebrou; existe justificativa técnica para a mudança.

## 3. Fluxo de uma requisição (arquitetura)

```
public/index.php
  → app/bootstrap.php
      ├─ Dotenv::createImmutable()->load()            (.env → $_ENV)
      ├─ valida DB_HOST/DB_DATABASE/DB_USERNAME (senão HTTP 500 amigável)
      ├─ configura error/exception handler (dev mostra erro, prod loga e mostra genérico)
      ├─ date_default_timezone_set('America/Sao_Paulo')
      ├─ session_start() com cookies seguros (httponly, samesite=Lax)
      ├─ SessionTimeoutMiddleware::handle()             (timeout de sessão global)
      ├─ IdentifyTenantMiddleware::handle()              (resolve tenant pelo HTTP_HOST — ver seção 4)
      ├─ gera csrf_token na sessão se não existir
      ├─ intercepta /storage/* (uploads: logos, anexos, imagens) antes do Router
      ├─ require routes/web.php  e  routes/api.php       (só REGISTRAM rotas, não executam nada)
      └─ Router::dispatch()                              (única linha que de fato executa a rota)
          ├─ casa método+URI contra App\Core\Router::$routes (suporta {param})
          ├─ roda middlewares da rota, na ordem do Router::group() (ex.: Auth, depois Permission:xxx)
          ├─ instancia App\Controllers\{Controller} e chama o método
          │     └─ Controller usa Model (App\Models\*) para regra de negócio/dados
          │           └─ Model usa App\Core\Database::getInstance() (PDO singleton)
          └─ Controller chama App\Core\View::render('modulo.view', $dados)
                └─ View monta header+conteúdo+footer do layout ('erp' | 'portal' | 'portal_public' | 'public' | 'none')
```

Middlewares registrados no `Router` (`app/Core/Router.php::$middlewares`):
`Auth` → `AuthMiddleware`, `Permission` → `PermissionMiddleware` (recebe parâmetro, ex.
`Permission:view_clients`), `PortalCliente` → `PortalClienteMiddleware`,
`WhatsappApiAuth` → `WhatsappApiAuthMiddleware`.

### Classes centrais (`app/Core/`)

| Classe | Papel | Ponto de atenção |
|---|---|---|
| `Router.php` | Registro (`get`/`post`/`group`) e dispatch de rotas | Suporta `{param}`; sem PUT/DELETE reais — tudo é GET/POST |
| `Database.php` | Singleton `PDO`, lê `config/database.php` | `ATTR_EMULATE_PREPARES=false`, fetch mode `FETCH_OBJ` (models retornam `object`, não array) |
| `Model.php` (abstract) | Base de todo Model — injeta `$this->pdo` | `getPdo()` exposto para casos avançados (uso restrito, superadmin) |
| `Auth.php` | Login/logout, hash Argon2ID, `Auth::can()`, `Auth::user()` | Login sempre passa por `TenantContext` — ver seção 4 |
| `TenantContext.php` | Contexto imutável do tenant da requisição atual | **Nunca** aceitar `tenant_id` de request/cliente — só o middleware define |
| `Permission.php` | Mapa `role => [permissões]` (RBAC) — ver seção 5 | Fonte de verdade das permissões; não há tabela de permissões no banco |
| `View.php` | Renderização de views + escolha de layout | `_layout` em `$dados` sobrescreve; senão infere por heurística (ver seção 6) |
| `Audit/AuditLogger.php` | Grava em `audit_logs` (try/catch interno) | Ação em `verbo_recurso`, nunca loga senha/token |
| `Form.php`, `UI.php` | Helpers de formulário/UI usados pelas views | — |
| `Logger.php` | Log de aplicação (arquivo), distinto de `AuditLogger` (banco) | Métodos como `->debug()`, `->error()`, `->auth()`, `->warning()` |
| `Mail.php` | Envio de e-mail (camada core; ver também `Services/MailService.php`) | — |

## 4. Multi-tenant (SaaS)

Referência completa: `docs/database/arquitetura-multitenant.md` e migration
`database/migrations/2026-08-12_multitenant_foundation.sql` (65KB — a fundação inteira).
Resumo operacional (leia `reference/multitenancy-e-banco.md` antes de tocar em
qualquer coisa que envolva `tenant_id`):

- O tenant é resolvido **exclusivamente pelo `HTTP_HOST`**, em `IdentifyTenantMiddleware`,
  executado no `bootstrap.php` antes de qualquer rota — inclusive login, portal e APIs.
  Em CLI (cron), o tenant vem de `TENANT_DEFAULT_SLUG` no ambiente do servidor, nunca de
  argumento de linha de comando.
- `App\Core\TenantContext` guarda o tenant da requisição em memória + `$_SESSION`, e
  **lança `LogicException` se consultado sem ter sido definido** — trate isso como
  "faltou passar pelo middleware", não como bug a silenciar.
- `users` é global (um usuário pode, no futuro, pertencer a mais de um tenant via pivô
  `user_tenants`). Todo o resto do negócio carrega `tenant_id` direto na tabela.
- Tabelas de catálogo global (não recebem `tenant_id`): `tenants`, `users`, `user_tenants`,
  `especialidades`, toda a família `cnes_*`/`_cnes_*`, `manual_artigos`, `manual_categorias`.
- Toda tabela operacional (clientes, financeiro, estoque, CRM, marketing, profissionais,
  apuração, RDV, portal, logs, integrações) tem `tenant_id` e deve ser filtrada por ele.
- **Toda query nova em Model que lida com dado de tenant deve filtrar por `tenant_id`**
  (via `TenantContext::id()`), do mesmo jeito que hoje já se filtra por `usuario_id` em
  código mais antigo — os dois convivem durante a migração incremental do legado.

## 5. RBAC / Permissões

Fonte de verdade única: `app/Core/Permission.php` (array PHP `role => [permissões]`, **não
existe tabela de permissões no banco**). Papéis atuais: `superadmin`, `admin`,
`financeiro`, `operador`, `leitura`, `user`. Lista completa e atualizada das permissões por
papel está em `reference/rbac-permissoes.md` — leia ali antes de decidir se uma permissão
já existe ou precisa ser criada.

Padrão de nome de permissão: `view_<recurso>`, `create_<recurso>`, `edit_<recurso>`,
`delete_<recurso>`, ou uma ação de negócio específica (`manage_leads`, `faturar_os`,
`import_notas_fiscais`). Para adicionar uma permissão nova: (1) adicionar a string no(s)
array(s) de papel em `Permission.php`; (2) usar `Auth::can('nome_permissao')` no
Controller; (3) usar `<?php if (Auth::can('nome_permissao')): ?>` na View para
esconder/mostrar UI; (4) se a rota for nova, agrupar com
`Router::group(["middleware" => ["Permission:nome_permissao"]], ...)` em `routes/web.php`.

## 6. Padrões de código e UI

Fonte completa: `docs/PADROES_TECNICOS.md` e `docs/form-layout-standard.md`. Pontos que
mais importam na prática:

- **Nomenclatura:** tabelas/colunas `snake_case` plural; classes PHP `PascalCase`;
  métodos/variáveis PHP `camelCase`; JS igual a PHP; CSS `kebab-case`.
- **Views/Layout:** `View::render('modulo.view', $dados)` mapeia `modulo.view` para
  `app/Views/modulo/view.php` (pontos → `/`). O layout é `erp` por padrão para telas
  internas, `portal` para tudo sob `Views/portal/*`, ou `none` se a própria view já
  incluir seu header manualmente (views legadas). Para telas novas, **não** inclua
  header/footer manualmente — deixe o `View::render` cuidar disso, ou passe
  `'_layout' => 'public' | 'portal_public'` explicitamente quando for tela pública.
- **Formulário "Enterprise" (padrão atual para CRUD com abas):** componente
  `app/Views/components/form/enterprise-form.php`, alimentado por um array `$formConfig`
  (title, subtitle, is_edit, tabs com `id/title/icon/locked/view`, footer_actions). Cada
  aba é uma view separada em `modulo/tabs/nome-enterprise.php`. JS de apoio:
  `public/assets/js/form-tabs.js` (classe `FormTabs`) + um `*-form.js` específico do
  módulo. Use este padrão para módulos novos com formulário complexo — é o padrão
  vigente, não o `form.php` simples usado em módulos mais antigos (que ainda funciona e
  não deve ser reescrito só por estar "desatualizado" — regra 6 das Regras de Ouro).
- **Auditoria:** `AuditLogger::log('verbo_recurso', ['id' => $id, ...])`, nunca dados
  sensíveis (senha, token) no contexto.
- **CSRF:** todo formulário interno inclui `<?= View::csrfField() ?>`; toda rota POST fora
  de exceções específicas espera esse token (`CsrfMiddleware`).
- **JSON de API/AJAX:** `{"success": true|false, "data"|"errors": ..., "message": "..."}`.

## 7. Mapa de diretórios — "onde fica X"

| Preciso de... | Vá em... |
|---|---|
| Ponto de entrada HTTP | `public/index.php` → `app/bootstrap.php` |
| Registrar/entender uma rota web | `routes/web.php` (451+ rotas, agrupadas por `Router::group`) |
| Rotas da API do bot WhatsApp | `routes/api.php` (protegidas por `WhatsappApiAuth`, header `X-API-Key`) |
| Config de conexão com banco | `config/database.php` (lê `.env`) |
| Variáveis de ambiente | `.env` (local, não versionado) / `.env.example` (template) |
| Autenticação / sessão / 2FA | `app/Core/Auth.php`, `app/Controllers/AuthController.php`, `app/Services/TwoFactorService.php`, `app/Views/auth/*` |
| Multi-tenant | `app/Core/TenantContext.php`, `app/Middlewares/IdentifyTenantMiddleware.php`, `app/Models/Tenant.php` |
| Permissões (RBAC) | `app/Core/Permission.php` |
| Auditoria | `app/Core/Audit/AuditLogger.php`, tabela `audit_logs` |
| Migrations SQL | `database/migrations/*.sql` (nomeadas `YYYY-MM-DD_descricao.sql`) — **use a skill `mysql57-migrations` para qualquer SQL novo** |
| Schema de referência (baseline) | `database/schema/2026-08-12_imagiflow_baseline.sql` (dump completo — grande, ~240KB) |
| Seeders | `database/seeders/*.sql` |
| Geração de PDF | `app/Lib/fpdf/` (biblioteca FPDF vendorizada) |
| Envio de e-mail | `app/Services/MailService.php`, `app/Core/Mail.php` |
| Integração Asaas (cobrança/boleto/pix) | `app/Services/AsaasService.php`, webhook em `routes/web.php` (`/api/webhooks/asaas`) |
| Integração Cora (banco) | `app/Services/CoraService.php`, webhook `/api/webhooks/cora` |
| Open Finance / Pluggy (extrato bancário) | `app/Services/OpenFinanceService.php`, `app/Services/PluggyService.php` |
| Importação de OFX bancário | `app/Services/OfxImportService.php` |
| Consulta CNPJ/CEP | `app/Services/CnpjService.php`, `app/Services/CepService.php` |
| Importação de dados CNES (DataSUS) | `app/Services/CnesImportService.php` (grande, 56KB) |
| Hub de IA (agentes, chat, RAG, prompts) | `app/Services/AI/*` (`AIService`, `AIProviderFactory`, `KnowledgeBaseService`, `SqlGuard`, providers em `Services/AI/Providers/`) |
| Criptografia de dados sensíveis | `app/Services/CryptoService.php` |
| OCR de despesas de viagem (RDV) | Motor primário é Tesseract.js **no navegador**; fallback opcional no servidor via `.env` (`OCR_SPACE_API_KEY`, `OPENAI_API_KEY`, ordem em `RDV_OCR_ENGINES`) |
| Cron jobs agendados | `cron/processar_alertas.php`; rotas `GET /api/cron/*` protegidas por `?key=CRON_KEY` do `.env` |
| Assets estáticos (CSS/JS/imagens) | `public/assets/` |
| Uploads/anexos servidos dinamicamente | `storage/` (interceptado no `bootstrap.php` antes do Router, não é servido pelo Apache direto) |
| Ferramentas/scripts de manutenção do repo | `tools/` (ex.: script de export de esquema) |
| Documentação viva e obrigatória do projeto | `docs/REGRAS_DE_OURO.md`, `docs/PADROES_TECNICOS.md`, `docs/form-layout-standard.md`, `docs/database/arquitetura-multitenant.md` |
| Mapa completo de módulos de negócio (Controllers/Models/Views/rotas) | `reference/modulos-e-rotas.md` deste skill |
| Lista completa de permissões atuais por papel | `reference/rbac-permissoes.md` deste skill |
| Detalhe de multi-tenant e classificação de tabelas | `reference/multitenancy-e-banco.md` deste skill |
| Regras de SQL/migration compatível com MySQL 5.7 | `.claude/skills/mysql57-migrations/SKILL.md` (skill separado, **sempre combinar com este**) |

### Nota sobre `docs/` vs `md/`

O repositório tem duas pastas de documentação com nomes de arquivo parcialmente
duplicados: `docs/` e `md/`. Pelo conteúdo, `md/` parece ser uma cópia mais antiga/paralela
de `docs/` (mesmos títulos, mesmos relatórios de auditoria pontuais). **Trate `docs/` como
a pasta viva** (é a citada pelos próprios documentos internos como fonte). A maioria dos
arquivos em ambas as pastas fora de `REGRAS_DE_OURO.md`, `PADROES_TECNICOS.md`,
`form-layout-standard.md` e `database/*` são **relatórios históricos de auditoria/correção
pontual** (ex.: `RELATORIO_CORRECOES.md`, `AUDITORIA_SEGURANCA.md`,
`DIAGNOSTICO_ENV.md`) — úteis como histórico de "o que já foi investigado/corrigido antes",
não como especificação viva do sistema atual.

## 8. Mapa de módulos de negócio

O mapa completo (Controller → Model(s) → Views → prefixo de rota → observações) para os
~25 domínios de negócio do sistema (Clientes, Fornecedores, Colaboradores, Médicos/Corpo
Clínico, CNES, Contratos & Apuração, Financeiro — Contas a Pagar/Receber/Bancárias,
Notas Fiscais, Estoque/Produtos, CRM completo, Marketing, Manutenção de equipamentos, RDV,
Portal do Cliente, Hub IA, Integrações, Configurações/Usuários/Perfil, Notificações,
Dashboard) está em **`reference/modulos-e-rotas.md`**. Abra esse arquivo antes de criar ou
alterar qualquer módulo — ele evita ter que abrir `routes/web.php` (52KB, 451+ rotas) e
percorrer `app/Controllers` (53 arquivos) / `app/Models` (73 arquivos) manualmente.

## 9. Protocolo para implementar algo novo neste sistema

Siga esta ordem para qualquer feature nova (módulo CRUD, campo novo, integração nova):

1. **Confirme o domínio no mapa de módulos** (`reference/modulos-e-rotas.md`) — talvez já
   exista Controller/Model parecido para copiar o padrão, em vez de inventar um novo.
2. **Banco de dados primeiro, se precisar de coluna/tabela nova.** Escreva a migration em
   `database/migrations/YYYY-MM-DD_descricao.sql` seguindo **as duas skills juntas**: as
   Regras de Ouro (seção 2 aqui) e `.claude/skills/mysql57-migrations/SKILL.md` (sintaxe
   MySQL 5.7 — sem `IF NOT EXISTS` em `ALTER TABLE`, sem CTE/window function). Lembre que
   não há runner automático: a migration é aplicada manualmente (phpMyAdmin/CLI) e deve
   terminar com uma seção de validação (`SHOW COLUMNS`/`SHOW TABLES`).
3. **Model** em `app/Models/NomeSingular.php`, estendendo `App\Core\Model`. Toda query que
   toca dado de tenant filtra por `tenant_id` (`TenantContext::id()`). Regra de negócio
   mora aqui, não no Controller.
4. **Permissão(ões)** em `app/Core/Permission.php`, no(s) papel(is) que devem ter acesso
   (padrão `view_/create_/edit_/delete_<recurso>` — ver seção 5 e
   `reference/rbac-permissoes.md`).
5. **Controller** em `app/Controllers/NomeController.php`. Cada método sensível começa
   checando `Auth::can('permissao')`. Sem SQL, sem HTML. Loga ações relevantes via
   `AuditLogger::log('verbo_recurso', [...])` (dentro do próprio `try/catch`, que já é
   interno ao `AuditLogger`, mas a chamada em si deve ficar dentro do fluxo protegido do
   Controller).
6. **Rota** em `routes/web.php`, dentro de `Router::group(["middleware" => ["Auth"]], ...)`
   e, para a ação específica, `Router::group(["middleware" => ["Permission:xxx"]], ...)` —
   siga o padrão já usado para o módulo Clientes (linhas ~100-136 de `routes/web.php`).
7. **View** — para CRUD novo com formulário complexo, use o padrão Enterprise com abas
   (seção 6): `modulo/form-enterprise.php` + `modulo/tabs/*-enterprise.php`, componente
   `app/Views/components/form/enterprise-form.php`. Para telas simples, `index.php`/
   `form.php` bastam. Nunca inclua header/footer manualmente — deixe `View::render`
   escolher o layout, ou passe `'_layout'` explicitamente se a tela for pública/portal.
8. **Menu/sidebar**, se a tela for nova no ERP interno: adicionar item em
   `app/Views/layout/erp_header.php`, condicionado por `Auth::can()` como qualquer outro
   botão sensível.
9. **Teste manual do fluxo completo** antes de considerar concluído (criar, editar,
   permissão negada, tela pública/portal se aplicável) — não há suíte automatizada de
   testes ativa neste projeto hoje (a seção 11 de `PADROES_TECNICOS.md` descreve um
   padrão de testes que é aspiracional, não implementado).
10. **Confira o checklist de PR** da seção 2 antes de considerar a tarefa pronta.

## 10. Mantendo este skill atualizado

Este skill é um mapa, não a verdade absoluta — ele foi escrito a partir de uma análise
pontual do sistema. Quando uma tarefa revelar que algo aqui ficou desatualizado (um
módulo novo apareceu, uma permissão mudou de nome, o fluxo de tenant mudou), vale a pena
atualizar o arquivo relevante (`SKILL.md` ou o arquivo em `reference/`) como parte da
própria tarefa, para que a próxima implementação já comece com o mapa certo.
