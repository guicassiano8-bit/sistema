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
- Error bags dos modais: `missaoRapida`, `recompensa`, `investimento`, `lancamento_gasto`, `lancamento_ganho`.
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
