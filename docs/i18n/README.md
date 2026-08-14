# Internacionalização do ERP IMAGINIFLOW

## Escopo da primeira entrega

A primeira entrega torna a internacionalização funcional para **pt-BR**, **en** e **es** nos layouts globais, no menu principal do ERP, no Portal do Cliente, no seletor de idioma e no conjunto de telas públicas de autenticação. A preferência é resolvida antes das rotas e aplica o seguinte fallback:

> `locale` da sessão → cookie `app_locale` → `pt_BR`.

Para usuários autenticados, a preferência também é persistida. O ERP usa `users.locale`; o Portal do Cliente usa `portal_clientes.locale`, pois essa é a tabela de credenciais real do portal. Ambas as atualizações permanecem restritas ao tenant atual.

| Área | Estado nesta entrega | Arquivo de chaves |
|---|---|---|
| Motor, fallback, moeda e data | Implementado | `app/Core/Lang.php` |
| Navegação global ERP | Implementada | `common.php` |
| Portal do Cliente | Implementado | `portal.php` |
| Login, recuperação, reset, 2FA e primeiro acesso | Implementado | `auth.php` |
| Clientes, financeiro, faturamento, CRM, estoque, manutenção, RDV e Hub IA | Estrutura pronta para expansão incremental | módulos próprios em `app/Lang/{locale}/` |

## Estrutura de arquivos

```text
app/
├── Core/Lang.php
├── Controllers/LocaleController.php
├── Lang/
│   ├── pt_BR/{common,auth,portal}.php
│   ├── en/{common,auth,portal}.php
│   └── es/{common,auth,portal}.php
└── Views/partials/language_selector.php
```

As chaves são organizadas por domínio. Use `common.*` para textos reutilizados, `auth.*` para a autenticação e `portal.*` para o Portal do Cliente. Cada módulo futuro deve usar seu próprio arquivo, como `clientes.php` ou `financeiro.php`, nos três locales.

## Como usar nas views e controllers

A função global `t()` está disponível após o bootstrap:

```php
<h1><?php echo htmlspecialchars(t('clientes.list_title')); ?></h1>
<button><?php echo htmlspecialchars(t('common.save')); ?></button>
```

Use sempre `htmlspecialchars()` ao renderizar texto em HTML. Para variáveis interpoladas, passe os valores explicitamente:

```php
echo htmlspecialchars(t('portal.session_active_for', ['tempo' => $tempoSessao]));
```

Para valores formatados por locale, use a instância central:

```php
use App\Core\Lang;

$valor = Lang::instance()->formatCurrency(1234.5);
$data = Lang::instance()->formatDate('2026-08-14');
```

## Troca de idioma

O seletor reutilizável envia um `POST /idioma` com token CSRF e mantém o caminho atual apenas quando é relativo ao mesmo domínio. A rota rejeita locale inválido e destinos externos. O idioma é salvo em sessão e cookie; quando há uma sessão autenticada, também é salvo na tabela apropriada do usuário ou do portal.

Não use parâmetro `?lang=` e não aceite URL de redirecionamento externa.

## Expansão por módulos

A tradução dos módulos de negócio deve ocorrer por fluxo funcional, evitando uma alteração massiva de textos que prejudique suporte e homologação. Para cada módulo:

1. Crie `app/Lang/pt_BR/<modulo>.php`, `en/<modulo>.php` e `es/<modulo>.php` com exatamente as mesmas chaves.
2. Substitua apenas textos de interface; não traduza valores persistidos, códigos de status, rotas, nomes de campos físicos, payloads, integrações ou regras fiscais.
3. Preserve PT-BR em documentos fiscais e objetos regulatórios até haver um modelo de documento separado aprovado pelo negócio.
4. Valide a sintaxe PHP e a igualdade de chaves dos três catálogos antes de publicar.

| Ordem recomendada | Módulo | Observação |
|---:|---|---|
| 1 | Clientes | Formulários, listagem e mensagens de validação. |
| 2 | Financeiro e Faturamento | Tradução de interface somente; manter regras e fiscalidade em PT-BR. |
| 3 | CRM e Marketing | Adaptar estágios, filtros e ações. |
| 4 | Estoque e Manutenção | Cobrir operações e movimentos. |
| 5 | RDV e Hub IA | Revisar termos técnicos e respostas apresentadas ao usuário. |

## Implantação no HostGator

1. Faça backup do banco novo e dos arquivos da aplicação.
2. Importe `database/migrations/2026-08-14_i18n_users_locale.sql` no banco `inlaud99_saasimagiflow` pelo phpMyAdmin.
3. Confirme que `users.locale` e `portal_clientes.locale` existem e que os valores iniciais são `pt_BR`.
4. Publique os arquivos da aplicação, incluindo `app/Lang/`, `app/Core/Lang.php`, `LocaleController.php` e a partial do seletor.
5. Faça logout, abra a tela de login e teste PT, EN e ES. Faça login com uma conta ERP e uma conta do Portal para confirmar a persistência independente em cada tenant.

> Não aplique a migration no banco de produção do InLaudo. Esta entrega foi preparada para o banco novo do Imagiflow e deve passar por homologação antes de qualquer uso em produção.

## Testes automatizados disponíveis

Os testes locais cobrem a sintaxe dos catálogos, igualdade de chaves entre idiomas, fallback, interpolação e formatação inicial. Eles estão em `.analysis/` e não são enviados ao repositório:

```bash
php .analysis/test_lang_engine.php
php .analysis/test_i18n_catalogue_coverage.php
```
