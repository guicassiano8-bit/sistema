# Tailwind CSS v4 no Sistema

O projeto usa **Tailwind v4** com configuração no próprio CSS. **Não existe `tailwind.config.js`.**
Os tokens estão no bloco `@theme` de `resources/css/app.css`.

## Instalação (Laravel 11/12)

```bash
npm i -D tailwindcss @tailwindcss/vite
# se o projeto veio com v3: npm rm autoprefixer postcss @tailwindcss/forms && rm tailwind.config.js postcss.config.js
```

- `vite.config.js` (já incluso): plugin `@tailwindcss/vite`, sem PostCSS.
- `resources/js/app.js`: `import './sistema';`
- `resources/css/app.css` começa com `@import "tailwindcss";` e usa `@source` para views, JS e `config/sistema.php`.

## Onde cada coisa mora

| O que | v3 (antes) | v4 (agora) |
|---|---|---|
| Cores, fontes, tamanhos de texto, espaços extras, raios, sombras, easings | `theme.extend` no `tailwind.config.js` | variáveis `--color-*`, `--font-*`, `--text-*`, `--spacing-*`, `--radius-*`, `--shadow-*`, `--ease-*` no `@theme` |
| Classe própria que aceita variante (`hover:glow`) | `@layer utilities { .glow {} }` | `@utility glow { … }` |
| Componentes (`.chamfer`, `.sys-btn`, `.sys-window`…) | `@layer components` | continua `@layer components` |
| Hover só em quem tem mouse | `future.hoverOnlyWhenSupported` | já é o padrão do v4 |

Como os nomes das variáveis viram classes (exemplos):
`--color-surface-raised` → `bg-surface-raised`
`--color-sys-glow` → `text-sys-glow`
`--spacing-tap` → `size-tap`, `min-h-tap`
`--text-hero` → `text-hero`
`--radius-pill` → `rounded-pill`

## Mudanças de classe feitas no código

| v3 | v4 | Por quê |
|---|---|---|
| `duration-fast` / `instant` / `slow` | `duration-160` / `duration-90` / `duration-400` | o v4 não tem tema de duração; aceita qualquer número |
| `ease-sys-out` | `ease-out` | o `--ease-out` do `@theme` foi redefinido para a curva do Sistema |
| `z-nav` / `z-fab` / `z-toast` | `z-40` / `z-45` / `z-60` | o v4 não tem tema de z-index |
| `bg-gradient-to-r` | `bg-linear-to-r` | nome novo do v4 |
| cursor de botão | regra em `@layer base` | o v4 tirou o `cursor: pointer` padrão dos botões |

## Verificação

`resources/css/app.css` compilou sem erro com **tailwindcss v4.3.3**. Foram conferidas no CSS gerado as classes com
variantes (`hover:glow`, `group-data-[state=done]:*`, `aria-[current=page]:*`,
`group-has-[[value=semanal]:checked]/rec:*`, `has-[details[open]]:*`), as arbitrárias
(`[--btn-bg:#2563EB]`, `bg-surface/[0.97]`) e os tokens (`size-tap`, `text-hero`, `rounded-pill`, `shadow-glow-sm`).

## Observação

O prefixo `!` (`!text-danger-text`) ainda funciona no v4. A forma nova é o sufixo (`text-danger-text!`), e dá para trocar quando quiser.
