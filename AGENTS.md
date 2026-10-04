<laravel-boost-guidelines>
=== .ai/sistema-pessoal rules ===

# Sistema Pessoal — Diretrizes do projeto

Diretrizes específicas deste projeto. O Laravel Boost mescla este arquivo no CLAUDE.md gerado; edite **aqui**, não no CLAUDE.md, e rode `php artisan boost:update` depois de qualquer alteração.

## Visão geral

Sistema pessoal de produtividade e finanças, gamificado. **Uso exclusivo de um único usuário** (o dono do projeto).

- Autenticação apenas com login. **Não existe tela de cadastro** nem recuperação de conta pública.
- Não há multi-tenancy, papéis ou permissões. Não adicionar essa complexidade.
- Idioma da interface: português do Brasil. Moeda: BRL (R$). Fuso: America/Sao_Paulo.

## Stack

- **Back-end:** Laravel (versão definida no `composer.json`), PHP, MySQL
- **Front-end:** Blade, **Tailwind CSS v4**, JavaScript puro (`resources/js/sistema.js`), CSS mínimo
- **Build:** Vite com o plugin `@tailwindcss/vite`
- **Não usar:** React, Vue, Livewire, Alpine ou bibliotecas de UI prontas, salvo pedido explícito.

## Comandos

```bash
php artisan serve        # servidor local
npm run dev              # Vite em modo desenvolvimento
npm run build            # build de produção
php artisan migrate      # rodar migrations
php artisan test         # rodar testes
php artisan boost:update # regenerar o CLAUDE.md após mudar estas diretrizes
```

## Módulos

| Módulo (código) | Nome na interface | Descrição |
|---|---|---|
| `tasks` | Missões | Tarefas avulsas e recorrentes que valem XP e ouro. Visões dia/semana/mês/ano (estilo Google Agenda). Tarefas podem ser transferidas entre dias. |
| `rewards` | Loja do Sistema | Recompensas cadastradas pelo usuário, compradas com ouro. Manter histórico de resgates. |
| `shopping` | Inventário | Lista de compras com categorias exibidas como raridade: Urgente, Importante, Dia a dia, Não importante. |
| `finance` | Tesouro | Ganhos, gastos e investimentos (CDBs e FIIs hoje; outros tipos no futuro), rendimentos e relatórios de patrimônio. |

**Regra de nomenclatura:** os nomes temáticos ("Missões", "Tesouro", "Inventário") existem **somente na interface**. Models, tabelas, controllers e variáveis usam nomes em inglês e literais (`Task`, `Reward`, `ShoppingItem`, `Transaction`, `Investment`).

## Regras de gamificação

Existem **duas moedas separadas**:

| | XP | Ouro |
|---|---|---|
| Finalidade | Progressão (nível e rank) | Comprar recompensas |
| Pode diminuir? | **Nunca** (exceto estorno de uma conclusão desfeita) | Sim, ao resgatar recompensas |
| Armazenamento | `xp_total` (só incrementa) | `gold` (saldo) |

- Concluir uma tarefa concede XP e ouro. Os valores ficam na própria tarefa.
- Desmarcar uma tarefa concluída **estorna** o XP e o ouro daquela conclusão.
- **Nível é calculado**, não salvo no banco. Fórmula: cada nível exige `100 × nível` de XP para passar ao próximo. Centralizar esse cálculo em um único lugar (ex: `app/Services/LevelService.php`).
- **Rank** por faixa de nível: 1–9 = E, 10–19 = D, 20–29 = C, 30–39 = B, 40–49 = A, 50+ = S.
- Toda movimentação de XP e ouro é registrada em uma tabela de histórico (ex: `point_transactions`), para permitir auditoria e estorno. Operações que alteram saldo rodam dentro de `DB::transaction()`.
- Resgatar recompensa sem ouro suficiente é bloqueado no back-end, não só na interface.

## Tarefas recorrentes

- A recorrência é uma regra (diária, semanal em dias específicos, mensal). Cada ocorrência concluída gera seu próprio registro de conclusão.
- Transferir uma ocorrência para outro dia não altera a regra de recorrência.

## Financeiro

- Valores monetários: coluna `DECIMAL(15,2)`. **Nunca usar float** para dinheiro.
- Exibir no formato brasileiro: `R$ 1.234,56`. Datas em `dd/mm/aaaa`.
- Ganhos e gastos são lançamentos com categoria e data. Investimentos têm tipo (CDB, FII, …) e seus rendimentos são registrados separadamente, para compor os relatórios de patrimônio e rendimentos.
- O financeiro **não** gera XP nem ouro, a menos que seja pedido.

## Design system (Tailwind CSS v4)

Direção: **mobile first**, tema **dark com tonalidade azul**, estética gamificada de "janela do Sistema" (inspiração em animes de progressão como Solo Leveling). Usar apenas a linguagem visual: **nunca** personagens, logos, nomes ou arte oficial de qualquer obra.

### Configuração no CSS, sem `tailwind.config.js`

O Tailwind v4 é configurado **dentro do CSS**. Não criar `tailwind.config.js`.

- **Tokens** ficam no bloco `@theme` de `resources/css/app.css`, com os namespaces do v4: `--color-*`, `--font-*`, `--text-*`, `--spacing-*`, `--radius-*`, `--shadow-*`, `--ease-*`. Cada token vira classe automaticamente (`--color-surface` → `bg-surface`, `text-surface`, `border-surface`).
- **Utilitários próprios** que precisam aceitar variantes (`hover:`, `md:` etc.) são criados com `@utility`: `glow`, `glow-s`, `scanlines`, `text-glow`, `tabular`.
- **Componentes CSS** (`.chamfer`, `.sys-btn`, `.sys-window`) ficam em `@layer components`.
- O v4 não tem tema de duração nem de z-index: usar classes numéricas (`duration-160`; `z-40` nav, `z-45` FAB, `z-60` toast).
- Sempre usar os tokens nas views. **Nunca** cores hex soltas nem valores arbitrários (`bg-[#0B1220]`) quando existe token.

Estrutura de referência:

```css
@import "tailwindcss";

@theme {
  --color-bg: #05070F;
  --color-surface: #0B1220;
  --color-surface-elevated: #111A2E;
  --color-primary: #3B82F6;
  --color-glow: #60A5FA;
  --color-glow-cyan: #38BDF8;
  --color-text: #E2E8F0;
  --color-text-muted: #94A3B8;
  --color-border: rgb(96 165 250 / 0.25);
  --color-success: #22D3EE;
  --color-warning: #F59E0B;
  --color-danger: #F43F5E;
  --color-legendary: #A855F7;

  --font-display: "Chakra Petch", sans-serif;
  --font-sans: "IBM Plex Sans", sans-serif;

  --spacing-tap: 2.75rem;          /* 44px: alvo mínimo de toque */
  --radius-pill: 9999px;
  --shadow-glow: 0 0 12px rgb(96 165 250 / 0.45);
  --shadow-glow-s: 0 0 6px rgb(96 165 250 / 0.35);
  --ease-out: cubic-bezier(0.16, 1, 0.3, 1);
  --ease-in: cubic-bezier(0.7, 0, 0.84, 0);
  --ease-snap: cubic-bezier(0.34, 1.56, 0.64, 1);
}

@utility glow {
  box-shadow: var(--shadow-glow);
}

@layer components {
  .chamfer { /* cantos chanfrados via pseudo-elementos, com --ch, --ch-border e --ch-bg */ }
}
```

Os valores acima são o ponto de partida; o `app.css` do projeto é a fonte da verdade.

### Tokens de cor

| Token | Classe | Uso |
|---|---|---|
| `--color-bg` | `bg-bg` | Fundo da aplicação |
| `--color-surface` | `bg-surface` | Cards/janelas |
| `--color-surface-elevated` | `bg-surface-elevated` | Modais, elementos sobrepostos |
| `--color-primary` | `bg-primary` | Ações principais |
| `--color-glow` / `--color-glow-cyan` | `text-glow`, `border-glow-cyan` | Brilho e destaques |
| `--color-text` | `text-text` | Texto principal |
| `--color-text-muted` | `text-text-muted` | Texto secundário |
| `--color-border` | `border-border` | Bordas das janelas |
| `--color-success` | `text-success` | Concluído, ganhos |
| `--color-warning` | `text-warning` | Alertas |
| `--color-danger` | `text-danger` | Gastos, exclusões |
| `--color-legendary` | `text-legendary` | Rank S, itens urgentes |

**Tipografia:** Chakra Petch (`font-display`) para títulos e números; IBM Plex Sans (`font-sans`) para textos. Números de XP, ouro e dinheiro com `tabular`.

### Regras de UI

- Projetar para **390px** primeiro, depois `md:` e `lg:`.
- Navegação: barra inferior no celular (< 768px), rail de 88px no tablet e sidebar de 264px no desktop (≥ 1024px). Um único `config/sistema.php` alimenta as três.
- **Regra dos 2 toques:** marcar missão ou item = 1 toque; criar missão, lançar gasto e transferir missão = 2 toques.
- Alvos de toque com no mínimo **44px** (`--spacing-tap`). Contraste mínimo AA. Foco sempre visível.
- Glow, blur e efeitos só em elementos de destaque (barra de XP, rank, botão primário, notificações). Não aplicar em tudo.
- Animações em CSS/JS puro, curtas (150–300ms), animando só `transform` e `opacity`, e respeitando `prefers-reduced-motion`.
- Ícones: Heroicons ou Lucide, em SVG inline.
- Feedback de conclusão no estilo do Sistema: `[MISSÃO CONCLUÍDA] +50 XP`.

## Convenções de código

### Laravel

- Controllers enxutos. Regras de negócio (pontuação, nível, recorrência, cálculos financeiros) ficam em **Services** em `app/Services/`.
- Validação sempre com **Form Requests**.
- Eloquent com relacionamentos definidos; evitar N+1 (usar `with()`).
- Rotas nomeadas e agrupadas por módulo, todas protegidas pelo middleware `auth`.
- Migrations com chaves estrangeiras e índices nas colunas usadas em filtros por data.
- Enums do PHP para valores fixos (categorias de compra, tipos de investimento, tipos de recorrência).

### Contratos entre back-end e front-end

- As rotas de toggle (`missoes.toggle`, `inventario.toggle`) respondem JSON quando `expectsJson()`: `{ done, player: {xp, xp_max, level, gold, rank, rank_changed}, leveled_up, toast }`.
- Um `View::composer` no layout `components.layouts.app` fornece `$jogador` e `$navBadges`.
- Error bags dos modais e formulários: `missaoRapida`, `recompensa`, `inventario`, `investimento`, `lancamento_gasto`, `lancamento_ganho`.
- Item do Inventário com `scheduled_date` entra na missão avulsa "Fazer Compras" daquele dia (10 XP, 0 ouro, uma por dia, criada por `ShoppingService`). Ela é apagada quando fica sem itens, exceto se já foi concluída. O vínculo é `shopping_items.task_id`; `Task::shoppingItems()` precisa de eager load ao renderizar `missoes._card`.
- A conclusão da missão "Fazer Compras" acompanha os itens: concluir (ou desfazer) a missão marca (ou desmarca) todos os itens; marcar o último item pendente conclui a missão, e desmarcar um item de missão concluída a estorna. A regra fica em `TasksService` (`alternarConclusao`, `sincronizarConclusaoComItens`) e `ShoppingService::alternar`, então vale em qualquer tela. Excluir o último item pendente, ou tirar a data dele (limpar ou trocar), também conclui a missão se só restarem itens comprados; "Limpar adquiridos" não conclui nada.
- `inventario.toggle` responde `{ done }` (o item em si não gera XP, ouro nem lançamento financeiro). Quando o toggle conclui ou estorna a missão "Fazer Compras", acrescenta `mission: { done }`, `player`, `leveled_up` e `toast`. O badge do Inventário (`$navBadges['inventario']`) conta os itens urgentes pendentes.
- Para animar a contagem do ouro após um redirect: `->with('ouro_anterior', $valor)`.

### Blade e front-end

- Componentes do sistema em `resources/views/components/sys/`, usados como `<x-sys.window>`, `<x-sys.xp-bar>`, `<x-sys.rank-badge>`, `<x-sys.mission-card>`, `<x-sys.toast>` etc.
- Componentes existentes: window, xp-bar, rank-badge, button, icon-button, icon, fab, input, select, segmented, modal, confirm, toast, toast-stack, bottom-nav, sidebar, player-hud, page-header, stat, money, mission-card, inventory-item, reward-card, chart, chart-legend, line-chart, bar-chart, hbar-chart, level-up. Reutilizar antes de criar um novo.
- Layout base em `resources/views/components/layouts/app.blade.php`.
- JavaScript em `resources/js/`, sem script inline extenso nas views.

### Geral

- Código (classes, métodos, variáveis, tabelas) em **inglês**. Textos da interface e comentários em **português**.
- Comentar apenas o "porquê", não o óbvio.
- Funcionalidade com regra de negócio (pontos, estorno, saldo, recorrência) tem teste em `tests/Feature`.

## Como trabalhar comigo

- Antes de mudanças grandes (nova tabela, novo módulo, refatoração), apresente um plano curto e espere aprovação.
- Explique decisões de arquitetura de forma didática: estou estudando Análise e Desenvolvimento de Sistemas e quero entender o porquê das escolhas.
- Não instale pacotes novos sem avisar e justificar.
- Se algo nestas diretrizes estiver desatualizado em relação ao código, avise.

=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.2. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
- Record a rule with `record-rule` only when the user explicitly asks for one. Instructions for the work at hand are not rules, no matter how emphatic: "remove this typo", "use X here" are work to do, not rules to record. Never record a rule on your own initiative, as a byproduct of a change, or to summarize what you just did. When the user does ask, pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Use `record-rule` rather than your native memory or notes tool, because native memory is personal and session-scoped, while only `.ai/rules` is shared with the team and persists in the repo.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.
- After the feature tests pass, ask the user to run the complete suite with `php artisan test --compact`.

</laravel-boost-guidelines>
