# Etapa 3 · Telas — wireframes e hierarquia de informação

Mobile first (390 × 844). Todas as telas logadas usam `<x-layouts.app>`:
HUD do jogador fixo no topo · conteúdo · FAB · bottom nav.
Legenda: `▓` preenchido · `□` checkbox · `⋮` menu · `◈` Ouro · `[+]` FAB.

Regra dos 2 toques, conferida por tela:

| Ação frequente | Toques |
|---|---|
| Marcar missão | 1 (checkbox) |
| Criar missão rápida | 2 (FAB → digitar → Enter) |
| Adicionar item ao inventário | 1 (campo fixo no topo → digitar → Enter) |
| Marcar item comprado | 1 |
| Lançar gasto | 2 (FAB → digitar valor → Enter) |
| Transferir missão p/ amanhã | 2 (⋮ → Amanhã) |

---

## 1. Login

```
┌──────────────────────────────┐
│                              │
│        [ SISTEMA ]           │  ← rótulo técnico
│   IDENTIFIQUE-SE, JOGADOR    │  ← H1 display
│   Acesso restrito.           │
│                              │
│ ┌ janela ───────────────────┐│
│ │ E-mail  [______________]  ││
│ │ Senha   [__________] (👁)  ││
│ │ □ Manter conectado        ││
│ │ [        ENTRAR         ] ││
│ └───────────────────────────┘│
│                              │
│  erro → borda vermelha +     │
│  mensagem sob o campo        │
└──────────────────────────────┘
```
**Hierarquia:** 1. formulário (única ação) · 2. identidade "Sistema" · 3. erro.
Sem cadastro, sem "esqueci a senha" (usuário único: redefina pelo tinker).

---

## 2. Status do Jogador (dashboard)

```
┌──────────────────────────────┐
│[C] LV.27  JOGADOR      ◈3.480│  ← HUD fixo (todas as telas)
│▓▓▓▓▓▓▓▓▓▓▓▓░░░░░░░ 1.240/2.000│
├──────────────────────────────┤
│ Qua, 23 set                  │
│ STATUS DO JOGADOR            │
│                              │
│┌[ MISSÕES DE HOJE ] 2/5 ────┐│  ← 1º: ação do dia
││ ▓▓▓▓▓▓░░░░ +180 XP hoje    ││
││ □ Estudar Laravel   +100 ⋮ ││
││ □ Revisar gastos     +30 ⋮ ││
││ ■ Leitura (riscada)  +50 ⋮ ││
││            Ver todas →     ││
│└───────────────────────────┘│
│┌[ ALERTA · INVENTÁRIO ] ────┐│  ← só aparece se houver lendário
││ ◆ Remédio  ◆ Conta de luz  ││
│└───────────────────────────┘│
│┌[ TESOURO ]─────────────────┐│
││ Patrimônio  R$ 48.920      ││
││ Mês: +ganhos  −gastos      ││
││ Passivo no mês  +R$ 312    ││
│└───────────────────────────┘│
│┌[ PRÓXIMA RECOMPENSA ]──────┐│
││ Jantar fora  ▓▓▓░░ faltam 720│
│└───────────────────────────┘│
│                        [+]   │  ← nova missão
│ STATUS MISSÕES LOJA INV TES  │
└──────────────────────────────┘
```
**Hierarquia:** 1. missões de hoje (a ação do dia) · 2. progresso (HUD, sempre
visível) · 3. alertas lendários (condicional) · 4. resumo do Tesouro ·
5. próxima recompensa (motivação).
**Desktop:** grid de 12 colunas: missões (7 col) | tesouro + inventário + recompensa (5 col).

---

## 3. Missões

### 3.1 Cabeçalho (comum às 4 visões)
```
│ MISSÕES              [Hoje]   │  ← "Hoje" só fora de hoje
│ [ DIA | SEMANA | MÊS | ANO ]   │  ← segmented (links)
│ [‹]   Qua, 23 set · Hoje  [›] │  ← navegador de período
```

### 3.2 Dia
```
│ ▓▓▓▓▓▓░░░░ 3/7 · +280 XP      │  ← progresso do dia
│┌[ ATRASADAS ] 1 ─────── danger┐│  ← só se houver
││ □ Pagar conta de luz  +40 ⋮  ││
│└──────────────────────────────┘│
│ MISSÕES DIÁRIAS               │
│ □ Leitura — 20 páginas  +50 ⋮ │
│ □ Treino                +80 ⋮ │
│ AVULSAS                       │
│ □ Estudar Laravel      +100 ⋮ │
│                         [+]   │
```
Dia vazio: janela com "Nenhuma missão. O Sistema aguarda." + botão Nova missão.

### 3.3 Semana (estilo agenda)
Mobile: lista vertical de 7 dias (Google Agenda "Programação").
```
│ DOM 20 … SEG 21 ▓▓▓▓▓ 4/4    │
│   ■ Leitura  ■ Treino …       │
│ TER 22  ▓▓▓░░ 3/5             │
│ QUA 23  ◀ HOJE (janela active)│
│   □ Estudar Laravel +100 ⋮    │
│   □ Revisar gastos   +30 ⋮    │
│ QUI 24  —  sem missões        │
```
Desktop (≥ lg): 7 colunas lado a lado com as missões em cartões compactos.
Toque no nome do dia → visão Dia daquela data.

### 3.4 Mês
```
│  D  S  T  Q  Q  S  S          │
│     1  2  3  4  5  6          │
│  ·  ▪  ▪  ▪  ·  ·  ·          │  ← barra de conclusão por dia
│  7  8 …          [23]         │  ← hoje: borda glow
│ ───────────────────────────── │
│ QUA, 23 SET · 2/5             │  ← dia selecionado
│ □ Estudar Laravel  +100 ⋮     │
```
Cada célula (44 px) mostra o número e uma barra com a % concluída.
Dias passados sem concluir 100% → barra âmbar; 100% → teal.

### 3.5 Ano
```
│ 2026 · 1.284 missões · 71%    │
│ ┌JAN────┐ ┌FEV────┐ ┌MAR────┐ │
│ │▪▪▫▪▪▪▪│ │▪▪▪▪▫▪▪│ │▪▪▪▪▪▪▪│ │  ← heatmap diário (azul, 5 tons)
│ └───────┘ └───────┘ └───────┘ │
│  … 12 meses, 3 colunas        │
│ menos ▫▪▪▪▪ mais              │
```
Toque no mês → visão Mês.

### 3.6 Criar / editar
- **Rápido (FAB → modal):** Título* · XP (10/30/50/100) · Data (hoje) · Repetir
  (não/diária/semanal) · `Mais opções →` · [Criar]. Enter envia.
- **Completo (página):** Título · Descrição · Data · Horário · XP · Ouro ·
  Dificuldade (rank E–S) · Recorrência (Nenhuma / Diária / Semanal com dias /
  Mensal no dia N) · Termina em (opcional). Rodapé fixo: [Excluir] [Salvar].

### 3.7 Transferir
⋮ → "Transferir para amanhã" (2 toques) ou "Escolher outro dia…" → bottom sheet:
```
│ [ TRANSFERIR ] Estudar Laravel│
│ [Amanhã] [Próx. segunda] [+7d]│
│ Data  [ 2026-09-24 ]          │
│              [Cancelar][Mover]│
```

---

## 4. Loja do Sistema

```
│ LOJA DO SISTEMA           [+] │  ← + = cadastrar recompensa
│ Saldo  ◈ 3.480 OURO           │  ← número grande, dourado
│ [ LOJA | HISTÓRICO ]          │
│┌────────────┐┌────────────┐   │
││  (ícone)   ││  (cadeado) │   │  ← 2 colunas no mobile
││ Café  ◈80  ││ Jantar ◈1.2k│  │
││ [TROCAR]   ││ ▓▓░ faltam │   │
│└────────────┘└────────────┘   │
```
Ordem: disponíveis (mais baratas primeiro) → bloqueadas (mais perto primeiro) → em recarga.

**Histórico:**
```
│ SETEMBRO · −1.230 Ouro · 6 trocas│
│ ◆ Episódio extra   21/09  −150│
│ ◆ Café especial    18/09   −80│
│ AGOSTO …                      │
```
**Hierarquia:** 1. saldo · 2. o que dá para trocar agora · 3. metas bloqueadas · 4. histórico.

---

## 5. Inventário (lista de compras)

```
│ INVENTÁRIO          12 itens  │
│┌──────────────────────────────┐│
││ [Adicionar item…      ] [+] ││  ← fixo abaixo do HUD, Enter adiciona
││ ◆Lend ◆Raro ◆Comum ◆Desc    ││  ← raridade (padrão: Comum)
│└──────────────────────────────┘│
│ ◆ LENDÁRIO · 2      (glow roxo)│
│ □ Remédio da farmácia  ×1  ✎ │
│ ◆ RARO · 3                    │
│ □ Ração do gato     ×2 kg ✎ │
│ ◆ COMUM · 5                   │
│ ◆ DESCARTÁVEL · 2  (apagado)  │
│ ▸ ADQUIRIDOS (4)   [Limpar]   │  ← <details> recolhido
```
**Hierarquia:** 1. adicionar (sempre à mão) · 2. lendários · 3. demais por raridade · 4. adquiridos.
Sem FAB: o campo fixo já é a ação principal.

---

## 6. Tesouro (financeiro)

Abas (links): `RESUMO | EXTRATO | ATIVOS | RELATÓRIOS`.

### 6.1 Resumo
```
│ TESOURO                       │
│ [RESUMO|EXTRATO|ATIVOS|RELAT.]│
│┌[ ATRIBUTO PRINCIPAL ]────────┐│
││ PATRIMÔNIO                  ││
││ R$ 48.920,15   ▲ 2,4% mês   ││  ← número herói
│└──────────────────────────────┘│
│┌GANHOS──┐┌GASTOS──┐┌PASSIVO─┐ │  ← 3 stats
││+5.200  ││−3.180  ││+312    │ │
│└────────┘└────────┘└────────┘ │
│ Saldo do mês ▓▓▓▓▓▓▓░░ +2.020  │
│ ÚLTIMOS LANÇAMENTOS  Ver todos│
│ Mercado   hoje   −R$ 42,90    │
│ [−] gasto (FAB)  [+ Ganho]    │
```

### 6.2 Extrato
```
│ [‹] Setembro 2026 [›]         │
│ [ TODOS | GANHOS | GASTOS ]   │
│ Entradas +5.200 · Saídas −3.180│
│ HOJE                           │
│ ● Mercado · Alimentação −42,90│
│ ONTEM …                        │
```

### 6.3 Ativos (investimentos)
```
│ Investido R$ 30.100 · Atual 31.940│
│ ▓▓▓▓▓▓▓▓▓▓▓▓░░░░░  CDB 68% FII 32%│  ← barra empilhada, rótulo direto
│┌CDB Banco X 110% CDI ─────────┐│
││ Aplicado 20.000  Atual 21.340 ││
││ Rendeu +1.340 (+6,7%) · vence ││
│└──────────────────────────────┘│
│┌FII XPTO11 · 120 cotas ───────┐│
││ Último dividendo R$ 0,92/cota ││
│└──────────────────────────────┘│
│ [+ Investimento] [+ Rendimento]│
```

### 6.4 Relatórios
- **Patrimônio · 12 meses:** linha/área (1 série, sem legenda; o título nomeia).
- **Ganhos × Gastos · 6 meses:** barras agrupadas, 2 séries (verde / rosa), legenda + rótulos.
- **Gastos por categoria · mês:** barras horizontais em um só tom, valor ao lado.
- Todos com tooltip ao tocar/passar e tabela sr-only equivalente.

**Hierarquia do Tesouro:** 1. patrimônio (atributo principal) · 2. fluxo do mês ·
3. renda passiva · 4. detalhes (extrato, ativos, relatórios).

---

## 7. Level up (sobreposição)

```
│░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░│  ← fundo escuro 85%
│      [ SISTEMA ]              │
│      LEVEL UP                 │  ← 72px, glow
│      LV. 27  →  28            │
│   (novo rank: se mudou)  [B]  │
│   ▓░░░░░░░░ 0 / 2.100 XP      │
│      [ CONTINUAR ]            │
```
Dispara por `sistema:levelup` (JS) ou `session('level_up')` (redirect).
Some ao tocar em Continuar, Esc ou em 6 s.

---

## Dados e rotas (o que as views esperam)

### Jogador em todas as telas
```php
// app/Providers/AppServiceProvider.php → boot()
View::composer('components.layouts.app', function ($view) {
    $view->with('jogador', auth()->user()->jogador());   // objeto com: nome, nivel, xp, xp_proximo, rank, ouro
});
```
Rank sugerido pelo nível: E 1–9 · D 10–19 · C 20–34 · B 35–49 · A 50–69 · S 70+.

### Locale (datas em português)
`.env` → `APP_LOCALE=pt_BR` e, no `AppServiceProvider::boot()`, `\Carbon\Carbon::setLocale('pt_BR');`
A semana começa no **domingo**: use `startOfWeek(Carbon::SUNDAY)` no controller da visão Semana.

### Rotas nomeadas
| Nome | Método · URI |
|---|---|
| `login` | GET/POST `/login` |
| `status` | GET `/` |
| `missoes.index` | GET `/missoes?v=&data=` |
| `missoes.create` · `missoes.store` | GET `/missoes/criar` · POST `/missoes` |
| `missoes.edit` · `missoes.update` · `missoes.destroy` | GET `/missoes/{missao}/editar` · PUT/DELETE `/missoes/{missao}` |
| `missoes.toggle` | PATCH `/missoes/{missao}/toggle` (JSON quando `expectsJson()`) |
| `missoes.transferir` | PATCH `/missoes/{missao}/transferir` (campo `data`) |
| `loja.index` · `loja.trocar` | GET `/loja?aba=` · POST `/loja/{recompensa}/trocar` |
| `recompensas.store` · `.edit` · `.update` · `.destroy` | resource sem index/show |
| `inventario.index` · `.store` · `.edit` · `.update` · `.destroy` | resource |
| `inventario.toggle` · `inventario.limpar` | PATCH `/inventario/{item}/toggle` · DELETE `/inventario/comprados` |
| `tesouro.index` | GET `/tesouro?aba=&mes=&filtro=` |
| `tesouro.lancamentos.store` | POST (campo `tipo` = gasto/ganho; gasto grava valor negativo) |
| `tesouro.investimentos.store` · `.edit` · `.update` · `.destroy` | resource |
| `tesouro.rendimentos.store` | POST (soma no `atual` do investimento e conta como passivo do mês) |

### Error bags dos modais (reabrem sozinhos com erro)
`missaoRapida` · `recompensa` · `investimento` · `lancamento_gasto` · `lancamento_ganho`
→ `$request->validateWithBag('missaoRapida', [...])`

### Valores em R$ digitados
Os campos de dinheiro usam `inputmode="decimal"` e aceitam "1.234,56". Normalize no FormRequest:
`str_replace(['.', ','], ['', '.'], $valor)`.
