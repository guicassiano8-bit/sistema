# Etapa 4 · Navegação — celular, tablet e desktop

## Mapa por largura

| Largura | Navegação | Jogador (nível/XP/Ouro) | Ação principal | Conteúdo |
|---|---|---|---|---|
| **< 768** celular | Bottom nav (5 itens, 64 px) | HUD fixo no topo | FAB acima da nav | 1 coluna, margem 16 |
| **768–1023** tablet | **Rail** 88 px à esquerda | HUD fixo no topo | FAB no canto (24 px da borda) | 1 coluna, margem 24 |
| **≥ 1024** desktop | **Sidebar** 264 px | Cartão no topo da sidebar | Ações rápidas na sidebar + atalhos | até 1024 px, margem 32; Status em 2 colunas |
| **≥ 1280** | igual | igual | igual | Semana vira 7 colunas |

Um só `config/sistema.php` alimenta bottom nav, rail e sidebar. Um rótulo mudado aparece nos três.

---

## Wireframes

### Celular (Etapa 3, sem mudanças)
```
┌──────────────────────────────┐
│[C] LV.27  JOGADOR      ◈3.480│ HUD
│▓▓▓▓▓▓▓▓▓▓▓▓░░░░░░░           │
│ conteúdo                     │
│                        [+]   │ FAB
│ STATUS MISSÕES LOJA INV TES  │ bottom nav
└──────────────────────────────┘
```

### Tablet (768–1023) — rail
```
┌──────┬───────────────────────────────────────┐
│ [C]  │ [C] LV.27  JOGADOR            ◈ 3.480 │ HUD (continua: mostra XP e Ouro)
│LV.27 │ ▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓░░░░░░░░░░           │
├──────┤                                       │
│ ⌁    │  STATUS DO JOGADOR                    │
│STATUS│  ┌[ MISSÕES DE HOJE ]──────────────┐  │
│ ◎    │  │ □ Estudar Laravel        +100 ⋮ │  │
│MISSÕES  │ …                               │  │
│ ⌂    │  └─────────────────────────────────┘  │
│ LOJA │                                       │
│ ⬡ ②  │                                       │
│ INV. │                                       │
│ ◇    │                                  [+]  │ FAB
│TESOURO                                       │
├──────┤                                       │
│ ⇥    │                                       │
│ SAIR │                                       │
└──────┴───────────────────────────────────────┘
```
**Por que um rail e não a sidebar inteira?** Em 768 px, 264 px de sidebar
tirariam um terço da tela. O rail mantém os 5 destinos a 1 toque e deixa
a coluna de conteúdo com cerca de 630 px.

### Desktop (≥ 1024) — sidebar
```
┌───────────────────────┬──────────────────────────────────────────────┐
│┌[C] JOGADOR ────────┐ │  [ QUA, 23 SET ]                             │
││    LV.27           │ │  STATUS DO JOGADOR                           │
││ ▓▓▓▓▓▓▓▓░░░░░      │ │ ┌[ MISSÕES DE HOJE ]──────┐┌[ TESOURO ]────┐ │
││ 1.240/2.000 XP     │ │ │ □ Estudar Laravel +100 ⋮││ R$ 48.920     │ │
││ OURO        ◈3.480 │ │ │ □ Revisar gastos   +30 ⋮││ +G  −G  ⚡P   │ │
│└────────────────────┘ │ │ ■ Leitura          +50 ⋮│└──────────────┘ │
│ [+ NOVA MISSÃO    ]   │ │                         │┌[ RECOMPENSA ]┐ │
│ [− LANÇAR GASTO   ]   │ │                         ││ Jantar ▓▓░░  │ │
│ [⬡ NOVO ITEM      ]   │ │                         │└──────────────┘ │
│ ───────────────────── │ └─────────────────────────┘                 │
│ MENU                  │                                             │
│▌⌁ Status           1  │  (sem HUD e sem FAB: estão na sidebar)      │
│ ◎ Missões          2  │                                             │
│ ⌂ Loja             3  │                                             │
│ ⬡ Inventário  ②    4  │                                             │
│ ◇ Tesouro          5  │                                             │
│ ───────────────────── │                                             │
│ ⇥ Sair                │                                             │
│ Atalhos: N G I 1–5    │                                             │
└───────────────────────┴─────────────────────────────────────────────┘
```

**Hierarquia da sidebar:**
1. quem sou eu agora (nível, XP, Ouro), sempre visível;
2. as 3 ações frequentes, cada uma a 1 clique ou 1 tecla;
3. os 5 destinos;
4. sair.

---

## Regras de comportamento

- **Item ativo:** `aria-current="page"`, texto `sys.glow` e um traço de energia.
  O traço fica no topo do item na bottom nav e à esquerda no rail e na sidebar.
  `match` aceita lista: a Loja fica ativa também em `recompensas.*`.
- **Badges:**
  - Missões: pendentes de hoje.
  - Inventário: itens lendários.
  - Aparecem como bolinha roxa sobre o ícone (celular e rail) e como contador à direita do rótulo (sidebar).
  - Vêm do composer (`$navBadges`); o `:badges` de uma tela sobrescreve.
- **Ações rápidas (desktop):**
  - Se o modal existe na tela atual, abre na hora.
  - Se não existe, o link leva para a tela certa com `?acao=nova-missao|gasto|item`, e o modal abre (ou o campo recebe o foco) ao carregar.
  - Na prática: um clique em qualquer tela.
- **Atalhos de teclado:** `N` missão · `G` gasto · `I` item · `1–5` telas.
  Ficam desligados enquanto você digita em um campo ou há um modal aberto,
  e ignoram Ctrl/⌘/Alt. Estão marcados com `aria-keyshortcuts`.
- **Sair:** no rodapé do rail/sidebar. No celular fica no fim da tela Status,
  para não ocupar espaço na bottom nav.
- **Sticky:**
  - Até 1023 px, o campo de adicionar do Inventário gruda abaixo do HUD. No desktop, gruda no topo.
  - Os rodapés de formulário (Salvar/Excluir) ficam acima da bottom nav no celular e a 24 px da borda a partir do tablet.
- **Performance:** a sidebar e a bottom nav usam fundo sólido a 97%, sem `backdrop-filter`.
  O glow aparece só no item ativo, com drop-shadow estático.

---

## Dados compartilhados (View::composer)

```php
// app/Providers/AppServiceProvider.php
use Illuminate\Support\Facades\View;

public function boot(): void
{
    \Carbon\Carbon::setLocale('pt_BR');

    View::composer('components.layouts.app', function ($view) {
        $user = auth()->user();
        $view->with('jogador', $user->jogador());             // nome, nivel, xp, xp_proximo, rank, ouro
        $view->with('navBadges', [
            'missoes'    => $user->missoes()->whereDate('data', today())->where('concluida', false)->count(),
            'inventario' => $user->itens()->where('categoria', 'urgente')->where('comprado', false)->count(),
        ]);
    });
}
```

## Acessibilidade

- Landmarks: `<aside aria-label="Navegação do Sistema">` com `<nav aria-label="Principal">`
  dentro. No celular, a bottom nav é outro `<nav aria-label="Principal">`, e só um
  deles fica visível por vez (o outro recebe `display: none`).
- O link "Pular para o conteúdo" leva ao `<main tabindex="-1">`.
- Alvos: itens do rail com 64 px, da sidebar com 44 px, da bottom nav com 78 × 64 px.
- O rótulo nunca some: o rail mostra texto curto embaixo do ícone.
