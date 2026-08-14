# RBAC — Papéis e Permissões Atuais

Fonte única e viva: `app/Core/Permission.php` (array PHP `role => [permissões]`). **Não há
tabela de permissões no banco** — qualquer permissão nova precisa ser adicionada neste
arquivo PHP diretamente. A tabela abaixo é um snapshot; se a tarefa for adicionar/alterar
permissão, confirme sempre no arquivo real antes de editar (é rápido e evita conflito de
merge).

Uso em código: `Auth::can('permissao')` (Controller e View) e `Permission:permissao` como
middleware de rota (`Router::group(["middleware" => ["Permission:xxx"]], ...)`).

## Papel `superadmin` (acesso mais amplo)

```
view_clients, create_clients, edit_clients, delete_clients
view_finance, manage_finance
view_plano_contas, create_plano_contas, edit_plano_contas, delete_plano_contas
view_fornecedores, create_fornecedores, edit_fornecedores, delete_fornecedores
view_contas_pagar, create_contas_pagar, edit_contas_pagar, delete_contas_pagar
view_contas_receber, create_contas_receber, edit_contas_receber, delete_contas_receber
view_faturamento, view_notas_fiscais, create_notas_fiscais, edit_notas_fiscais,
  delete_notas_fiscais, import_notas_fiscais
view_integracoes, manage_integracoes
view_hub_ia, manage_hub_ia
view_crm, manage_leads, manage_oportunidades
view_manutencao, create_os, edit_os, delete_os, faturar_os
view_profile, edit_profile
view_users, manage_users
view_settings, manage_settings
view_colaboradores, create_colaboradores, edit_colaboradores, delete_colaboradores
```

## Papel `admin`

Igual a `superadmin`, **exceto**: não tem `view_users` / `manage_users` (gestão de
usuários do sistema é exclusiva de `superadmin`).

## Papel `financeiro`

```
view_clients
view_finance
view_plano_contas, create_plano_contas, edit_plano_contas, delete_plano_contas
view_fornecedores, create_fornecedores, edit_fornecedores, delete_fornecedores
view_contas_pagar, create_contas_pagar, edit_contas_pagar, delete_contas_pagar
view_contas_receber, create_contas_receber, edit_contas_receber, delete_contas_receber
view_faturamento, view_notas_fiscais, import_notas_fiscais
view_profile, edit_profile
view_colaboradores
```

Sem `manage_finance`, sem criar/editar/apagar notas fiscais, sem CRM, sem manutenção, sem
Hub IA, sem integrações.

## Papel `operador`

```
view_clients, create_clients, edit_clients
view_crm, manage_leads, manage_oportunidades
view_manutencao, create_os, edit_os
view_profile, edit_profile
view_colaboradores, create_colaboradores, edit_colaboradores
```

Sem financeiro, sem faturamento, sem apagar nada (clientes, OS, colaboradores) — perfil
operacional de linha de frente.

## Papel `leitura` (somente visualização)

```
view_clients
view_finance, view_plano_contas, view_fornecedores, view_contas_pagar,
  view_contas_receber, view_faturamento, view_notas_fiscais, view_integracoes, view_hub_ia
view_crm
view_manutencao
view_colaboradores
view_profile
```

Nenhum `create_*`/`edit_*`/`delete_*`/`manage_*` — só leitura, e mesmo assim não cobre
todos os módulos (ex.: sem `view_plano_contas` teria erro — na verdade tem; confirme
sempre no arquivo real se um módulo novo precisa ganhar sua própria entrada `view_*` em
`leitura`, porque módulos novos **não são automaticamente visíveis** para este papel).

## Papel `user` (menor privilégio)

```
view_profile, edit_profile
```

## Ao criar uma permissão nova

1. Escolha o nome seguindo o padrão do resto do arquivo: `view_<recurso>`,
   `create_<recurso>`, `edit_<recurso>`, `delete_<recurso>`, ou um verbo de negócio
   específico se não for CRUD puro (`manage_leads`, `faturar_os`, `import_notas_fiscais`).
2. Adicione a string manualmente em **cada** array de papel que deve tê-la — não existe
   herança automática entre papéis (mesmo que `admin` pareça "herdar" de `superadmin`,
   isso hoje é feito por duplicação literal do array em `Permission.php`).
3. Pense em `leitura` também: se o módulo faz sentido ser visualizado por um perfil
   somente-leitura, adicione o `view_<recurso>` lá.
4. Use a permissão no Controller (`Auth::can()`), na View (esconder botão/link) e na rota
   (`Permission:<recurso>` no `Router::group`).
