# Etapa 5 · Microinterações

Só CSS e JS puro (`app.css` + `sistema.js`), sem biblioteca.

**Princípios**

1. **A resposta vem em até 100 ms, a animação vem depois.** Marcar uma missão muda o
   estado na hora; o brilho e os números vêm em seguida e nunca atrasam o próximo toque.
2. **Loop só em `transform` e `opacity`.** Glow (`filter`) anima no máximo 1 vez por
   ação, sem blur animado e sem `backdrop-filter`. Isso roda liso em celular intermediário.
3. **A gamificação celebra, mas não bloqueia.** Nenhuma animação impede tocar em outra
   coisa, a não ser a tela de subida de nível, que dá para fechar com Esc ou com o botão
   em ~1 s e fecha sozinha em 6 s.
4. **Com `prefers-reduced-motion`, tudo vira troca instantânea.** Nenhuma informação se
   perde: o toast continua, os números mudam direto e o estado muda igual.

## Tokens de movimento

No Tailwind v4 não existe tema de duração: use a classe numérica (`duration-160`). Os easings ficam no `@theme` e sobrescrevem os padrões do Tailwind (`ease-out`, `ease-in`) e criam `ease-snap`.

| Token | Valor | Uso |
|---|---|---|
| `duration-90` | 90 ms | press de botão, checkbox |
| `duration-160` | 160 ms | hover, foco, troca de cor |
| `duration-240` | 240 ms | toast entrando, risco no título, elementos do level up |
| `duration-400` | 400 ms | barra de XP enchendo, modal |
| `duration-900` | 900 ms | anel e varredura do level up |
| `ease-out` (`--ease-out`) | `cubic-bezier(.16,1,.3,1)` | entradas (rápido no começo, assenta suave) |
| `ease-in` (`--ease-in`) | `cubic-bezier(.7,0,.84,0)` | saídas |
| `ease-snap` (`--ease-snap`) | `cubic-bezier(.2,.9,.1,1.2)` | pequenos "pops" com overshoot (check, número) |

## Catálogo

| # | Interação | Gatilho | O que se move | Duração · easing | Reduced motion | Onde |
|---|---|---|---|---|---|---|
| M1 | Entrada da tela | carregar a página | filhos do `<main>`: opacity 0→1, Y 6px→0, escalonados 30 ms (máx. 5) | 200 ms · ease-out | aparece direto | `.sys-enter` (layout) |
| M2 | Press | toque/clique em botão | Y +1px, fundo `--btn-press`, glow some | 90 ms · ease-out | troca de cor só | `.sys-btn:active` |
| M3 | Hover / foco | ponteiro ou Tab | borda 25%→50% + glow; foco = anel 2px ciano | 160 ms · ease-out | instantâneo | `.sys-btn`, `.is-interactive`, `:focus-visible` |
| M4 | **Missão concluída** | toque no check | ① check entra com overshoot (scale .6→1) ② risco corre no título ③ card dá 1 lampejo teal ④ "+XP" flutua ⑤ vibração de 12 ms ⑥ toast | ① 200 ms snap ② 240 ms ③ 600 ms ④ 800 ms | só troca de estado + toast | `mission-card`, `sistema.js › toggle()` |
| M5 | Risco animado | `data-state="done"` | gradiente de 1px cresce 0→100% da largura do texto | 240 ms · ease-out | risco aparece pronto | `.sys-strike` |
| M6 | "+50 XP" flutuante | concluir missão | texto sobe 0→−36px, escala 0,9→1,06→1, some | 800 ms · ease-out | **não aparece** (o toast já informa) | `Sistema.floatXp()` |
| M7 | Número que muda | Ouro, contadores "3/7", XP do dia | conta do valor antigo ao novo + pulinho 1→1,14→1 | 300–700 ms · ease-out cúbico | valor muda direto | `Sistema.countTo()`, `.sys-bump` |
| M8 | Toast | missão, loja, erro | entra de cima (−12px); barra de tempo encolhe; sai "fechando" em Y | entra 240 ms · vive 3,5 s (level 5 s) · sai 180 ms ease-in | sem deslocamento; o **tempo** é mantido | `toast.blade.php`, `.sys-toast-timer` |
| M9 | **Level up** | `sistema:levelup` / `session('level_up')` | coreografia de 1,1 s (tabela abaixo) | ver abaixo | tudo aparece junto, sem movimento | `level-up.blade.php`, `.lu-*` |
| M10 | Troca de tela | clicar em link | HUD, sidebar e bottom nav **ficam parados**; conteúdo faz crossfade; o traço do item ativo **desliza** até o novo item | sai 120 ms ease-in · entra 180 ms ease-out · traço 260 ms | corte seco | `@view-transition` (Chrome/Edge 126+, Safari 18.2+; nos outros a troca é normal) |
| M11 | Modal / bottom sheet | abrir/fechar | a janela "se desenha": linha fina → altura total | abre 280 ms ease-out · fecha 180 ms ease-in | aparece/some | `.sys-dialog` |
| M12 | Barra de XP | ganhar XP | largura anima + faixa de brilho atravessa 1× | 400 ms + brilho 1,2 s (atraso 250 ms) | largura muda direto | `.sys-xp-fill`, `Sistema.xp.set()` |
| M13 | Item lendário | sempre visível | ponto roxo pulsa só em opacidade | 2,4 s loop · ease-in-out | parado | `.sys-pulse` |
| M14 | Carregando | botão enviando / gráfico | spinner gira; skeleton com brilho passando | 700 ms / 1,4 s loop · linear | parado | `.sys-spinner`, `.sys-skeleton` |
| M15 | Menu ⋮ | abrir `<details>` | painel desce 12px + fade | 160 ms · ease-out | aparece | `.sys-menu` |

### M4 em linha do tempo (missão concluída)
```
0 ms     estado muda (check, cores, contadores começam a contar)   ← resposta imediata
0–200    check "pop"            ███
0–240    risco no título        ████
0–600    lampejo teal no card   ██████████
0–800    "+50 XP" sobe e some   █████████████
~150     toast entra (quando o servidor responde)   ████ … 3,5 s … ▁
~150     HUD: barra de XP anima 400 ms + brilho; Ouro conta 600 ms
```

### M9 em linha do tempo (level up)
```
0     fundo escurece 200 ms · anel cresce 0,6→1 (900 ms)
100   varredura desce pela tela (900 ms) · [ SISTEMA ] sobe
150   "LEVEL UP": espaçamento .5em→.04em + estica na vertical (700 ms)
500   LV.27 → LV.28 sobe (300 ms)
700   número novo dá um pulinho (420 ms, snap)
800   novo rank entra com giro leve (420 ms, snap) — só se mudou
850   barra de XP aparece · 950 ms enche até o XP que sobrou
950   botão Continuar (foco já está nele)
6 s   fecha sozinho
```

## Performance (celular intermediário)

- Os glows estáticos usam `drop-shadow` sem animação. O único `filter` animado é o lampejo
  do M4 (1 elemento, 600 ms, uma vez).
- Nenhum `backdrop-filter`: o fundo das barras é sólido a 97%.
- O "+XP" é 1 elemento `position: fixed`, removido no `animationend`.
- Contadores usam `requestAnimationFrame`, no máximo ~40 quadros.
- A View Transition é um recurso extra: o navegador captura as camadas, sem custo de JS.

## Acessibilidade

- `prefers-reduced-motion: reduce` zera duração e atraso de todas as animações e transições.
  Há duas exceções:
  - o **tempo** do toast (a barra fica invisível, mas o toast continua 3,5 s na tela);
  - o "+XP" flutuante é removido (a informação já está no toast).
- O toast pausa com hover **e** com foco, e dá para fechar pelo botão ×.
- Os números que contam têm valor final real no DOM; leitores de tela leem o
  `aria-valuetext` da barra, não os quadros intermediários.
- Nenhuma animação pisca mais que 3 vezes por segundo.

## Para ativar os extras no Laravel

- **Ouro contando após trocar recompensa (ou qualquer redirect):**
  `return back()->with('ouro_anterior', $ouroAntes)->with('sys_toast', [...])`.
  O HUD e a sidebar contam do valor antigo até o novo.
- **Contadores ao vivo:** as views Status, Dia, Semana e Mês já têm `data-progress-scope`.
  Em uma tela nova, marque o container com `data-progress-scope` e os números com
  `data-progress-done`, `data-progress-total`, `data-progress-xp` e `data-progress-bar`.
