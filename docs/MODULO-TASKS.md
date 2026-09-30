# Módulo `tasks` (Missões)

## Visão geral

Gerencia as missões do jogador: tarefas avulsas e recorrentes que, ao serem concluídas, concedem XP e ouro. Oferece as visões dia/semana/mês/ano, filtros, transferência entre dias, cancelamento e encerramento de séries recorrentes. Depende de `LevelService` e do livro-razão `point_transactions` (gamificação) e alimenta a tela Status (`StatusService`).

Nome na interface: **Missões**. No código: `Task`, `RecurringTask`, `TasksController`, `TasksService`.

## Estrutura de arquivos

```
app/
├── Console/Commands/GerarMissoesRecorrentes.php   # comando missoes:gerar-recorrentes (agendado diariamente)
├── Enums/
│   ├── TaskStatus.php          # pending | done | cancelled
│   ├── TaskRank.php            # E..S, dificuldade só visual (não altera XP/ouro)
│   ├── Frequency.php           # daily | weekly | monthly | yearly (compartilhado com o financeiro)
│   ├── PointTransactionType.php# task_completed | task_reverted | reward_redeemed | adjustment
│   └── PointCurrency.php       # xp | gold
├── Http/
│   ├── Controllers/TasksController.php    # controller enxuto; delega tudo ao TasksService
│   └── Requests/
│       ├── TaskStoreRequest.php     # criação: modal rápido (bag missaoRapida) ou form completo (completo=1)
│       ├── TaskUpdateRequest.php    # form completo; expõe formRules() reutilizado pelo store
│       ├── TaskTransferRequest.php  # valida a nova data
│       └── TaskFilterRequest.php    # filtros q, status, rank, tipo
├── Models/
│   ├── Task.php                # missão avulsa ou ocorrência de uma série
│   ├── RecurringTask.php       # molde (regra) da série
│   └── PointTransaction.php    # livro-razão imutável de XP/ouro
└── Services/
    ├── TasksService.php        # regras de negócio (CRUD, conclusão, estorno, visões)
    ├── RecurrenceService.php   # regra → ocorrências (datas e geração)
    ├── StatusService.php       # consumidor: dados da tela Status
    └── LevelService.php        # nível/rank a partir do xp_total (fora do módulo, usado por ele)
database/
├── migrations/  (recurring_tasks, tasks, point_transactions, add_currency_to_point_transactions,
│                 add_gold_and_rank_to_tasks_tables)
└── factories/   TaskFactory, RecurringTaskFactory
resources/views/missoes/   index, form, _card, _modais, visoes/{dia,semana,mes,ano}
resources/js/sistema.js    # toggle via fetch (JSON) e modal de transferência
routes/web.php, routes/console.php, config/sistema.php
tests/Feature/  Http/Controllers/TasksControllerTest, MissoesFormTest, MissoesGestaoTest,
                RecurrenceServiceTest, StatusTest
```

## Fluxo principal

**Criar missão**
1. `POST /missoes` → `TaskStoreRequest` (regras enxutas no modal; `TaskUpdateRequest::formRules()` se `completo=1`).
2. `TasksController::store` → `TasksService::criar`.
3. Sem recorrência: `Task::create`. Com recorrência: numa `DB::transaction`, cria `RecurringTask` (molde), a primeira `Task` e chama `RecurrenceService::gerar(molde:)` para preencher os próximos 30 dias.
4. Form completo redireciona ao dia da missão com toast; o modal apenas faz `back()`.

**Concluir / desmarcar** (`PATCH /missoes/{missao}/toggle`)
1. `TasksService::alternarConclusao` → bloqueia cancelada (422) → `concluir()` ou `desmarcar()`.
2. Em `DB::transaction`: atualiza `status`/`completed_at`, grava **dois** lançamentos em `point_transactions` (XP e ouro), ajusta `users.xp_total` e `users.ouro`.
3. Resposta JSON (se `expectsJson()`): `{ done, player, leveled_up, toast }`; caso contrário `back()`.

**Geração de recorrentes**: `schedule:run` → `missoes:gerar-recorrentes` (diário) → `RecurrenceService::gerar(hoje + 30 dias)` → `Task` por data (`firstOrCreate` por `occurrence_date`).

**Listagem**: `GET /missoes?v=dia|semana|mes|ano&data=Y-m-d&q=&status=&rank=&tipo=` → `TasksService::dadosDaTela` → view `missoes.index` + `visoes/*`.

## Componentes

### Rotas (`routes/web.php`, todas com `auth`)

| Método | URI | Nome | Ação |
|---|---|---|---|
| GET | `/missoes` | `missoes.index` | listagem |
| GET | `/missoes/create` | `missoes.create` | form completo |
| POST | `/missoes` | `missoes.store` | criar |
| GET | `/missoes/{missao}/edit` | `missoes.edit` | form de edição |
| PUT/PATCH | `/missoes/{missao}` | `missoes.update` | atualizar |
| DELETE | `/missoes/{missao}` | `missoes.destroy` | excluir |
| PATCH | `/missoes/atrasadas/hoje` | `missoes.atrasadas-hoje` | trazer atrasadas para hoje (declarada antes do resource) |
| PATCH | `/missoes/{missao}/cancelar` | `missoes.cancelar` | cancelar |
| DELETE | `/missoes/{missao}/serie` | `missoes.encerrar-serie` | encerrar série |
| PATCH | `/missoes/{missao}/toggle` | `missoes.toggle` | concluir/desmarcar |
| PATCH | `/missoes/{missao}/transferir` | `missoes.transferir` | mudar a data |

O parâmetro de rota é `{missao}` (binding implícito de `Task`).

### `TasksService` (`app/Services/TasksService.php`)

| Método | Parâmetros | Retorno | Erros |
|---|---|---|---|
| `dataDaTela(?string $valor)` | data `Y-m-d` (opcional) | `Carbon`; inválida/vazia/"transbordada" (ex. `2026-02-31`) → hoje | — |
| `navegacao(string $visao, Carbon $data)` | visão, data | `[anterior, próxima]` | `UnhandledMatchError` se visão inválida (o controller já sanitiza) |
| `periodo(string $visao, Carbon $data)` | idem | `[início, fim]`; semana e grade do mês começam no **domingo** | idem |
| `missoesDoDia(Builder, Carbon)` | query, dia | coleção; se o dia é hoje, inclui pendentes atrasadas; concluídas por último, depois por `start_time` | — |
| `semana(Builder, Carbon, Carbon)` | query, início, fim | coleção de `['data', 'missoes']` | — |
| `resumoPorDia(Builder, Carbon, Carbon)` | idem | `['Y-m-d' => ['total','feitas']]` (um `GROUP BY`) | — |
| `aplicarFiltros(Builder, array)` | `q`, `status`, `rank`, `tipo` | `Builder` (`q` escapa `%`, `_` e `\`) | — |
| `dadosDaTela(string, Carbon, array = [])` | visão, data, filtros | array para a view (`anterior`, `proxima` + extras da visão) | — |
| `criar(array $dados)` | `titulo`, `xp`, `data` (obrigatórios); `descricao`, `ouro`, `rank`, `horario`, `recorrencia`, `recorrencia_dias`, `recorrencia_dia_mes`, `termina_em` | `Task` criada (a primeira ocorrência, se recorrente) | exceções de banco (transação faz rollback) |
| `atualizar(Task, array, bool $aplicarFuturas = false)` | missão, dados, flag | `Task` | idem |
| `cancelar(Task, User)` | missão, usuário | `void` | — |
| `excluir(Task, User)` | missão, usuário | `void` | — |
| `trazerAtrasadasParaHoje()` | — | `int` (quantidade movida) | — |
| `encerrarSerie(Task)` | missão | `void` | HTTP 422 se a missão não tem molde |
| `alternarConclusao(Task, User)` | missão, usuário | `array{done, jogador, leveled_up, rank_changed}` | HTTP 422 se cancelada |
| `transferir(Task, string $data)` | missão, `Y-m-d` | `void` | HTTP 422 se não estiver pendente |

Exemplo (extraído do fluxo do controller):

```php
$resultado = $this->tasks->alternarConclusao($missao, $request->user());
// ['done' => true, 'jogador' => Jogador, 'leveled_up' => false, 'rank_changed' => false]

$this->tasks->transferir($missao, '2026-10-05');
$movidas = $this->tasks->trazerAtrasadasParaHoje();
```

### `RecurrenceService`

| Método | Parâmetros | Retorno |
|---|---|---|
| `datasNoIntervalo(RecurringTask $molde, Carbon $de, Carbon $ate)` | molde, intervalo (obrigatórios) | `Collection<Carbon>` das datas em que a regra cai, respeitando `starts_on`/`ends_on` |
| `gerar(?Carbon $ate = null, ?RecurringTask $molde = null)` | limite (padrão hoje + 30 dias), molde único opcional (padrão: todos com `is_active`) | `int` ocorrências criadas |

Constante: `JANELA_PADRAO_DIAS = 30`. Sem exceções próprias.

```php
$this->recorrencia->gerar(molde: $recurringTask);      // uso em TasksService::criar
$recorrencia->gerar(today()->addDays(30));             // uso no comando agendado
```

### Comando

`php artisan missoes:gerar-recorrentes {--dias=30}` — gera ocorrências para os próximos N dias (mínimo 1). Saída: `"{n} missão(ões) criada(s)."`.

### Form Requests

- `TaskStoreRequest`: modal → `titulo` (≤255), `xp` ∈ {10,30,50,100}, `data`, `recorrencia` ∈ {diaria, semanal}; erros na bag `missaoRapida`. Com `completo=1` usa `TaskUpdateRequest::formRules()` e a bag `default`.
- `TaskUpdateRequest::formRules()`: `titulo` ≤120, `descricao` ≤2000, `data`, `horario` (`H:i`), `xp` 0–100000, `ouro` 0–100000 (opcional), `rank` (enum `TaskRank`), `recorrencia` ∈ {nenhuma, diaria, semanal, mensal}, `recorrencia_dias` (obrigatório se semanal, 1–7 ISO), `recorrencia_dia_mes` (obrigatório se mensal, 1–31), `termina_em` ≥ `data`, `aplicar_futuras`.
- `TaskTransferRequest`: `data` obrigatória, `Y-m-d`.
- `TaskFilterRequest`: `q` ≤120, `status`, `rank`, `tipo` ∈ {avulsa, recorrente}.

### Models

- `Task`: casts para enums/datas; relações `recurringTask()`, `pointTransactions()` (morph, alias `task`); scopes `pending`, `overdue`, `betweenDates`; accessors `is_recurring`, `is_overdue`, `is_done`.
- `RecurringTask`: relação `tasks()`; scope `active` (ativo e dentro da vigência).
- `PointTransaction`: `source()` (morph), `balance(PointCurrency)`; **lança `LogicException` em update e delete** (correções só por novo lançamento).

## Dependências

- **Tabelas**: `tasks`, `recurring_tasks`, `point_transactions`, `users` (colunas `xp_total` e `ouro`).
- **Serviços/classes internas**: `LevelService::calcular`, `App\Support\Jogador`, `User::jogador()`.
- **Pacotes externos**: nenhum específico do módulo. `Frequency::toRRule()` cita `rlanvin/php-rrule`, mas o módulo não usa (ver Pontos de atenção).
- **Variáveis de ambiente**: nenhuma específica.
- **Agendador**: `Schedule::command('missoes:gerar-recorrentes')->daily()` em `routes/console.php`.
- **Morph map**: `AppServiceProvider` grava `task` em `source_type`.
- **Front-end**: `resources/js/sistema.js` (formulários `data-mission-toggle`, modal com `data-transfer-form`).

## Regras de negócio e decisões importantes

- **XP e ouro por missão**: `points` (XP) e `gold` ficam na própria `Task` e no molde. Sem ouro informado (modal rápido), `gold = xp`. A migração de 29/09 copiou `points` para `gold` nos registros existentes. `rank` é só visual.
- **Dois lançamentos por evento**: cada conclusão/estorno cria um lançamento de XP e outro de ouro, mesmo com ouro 0, para manter a auditoria simétrica.
- **Estorno**: desmarcar grava lançamento negativo `task_reverted`; `xp_total` e `ouro` usam `max(0, …)`.
- **Editar missão concluída** não altera `points`/`gold`, para o estorno devolver exatamente o que foi concedido.
- **Cancelar/excluir uma concluída** estorna antes. Missão cancelada não pode ser alternada nem transferida, e não há rota para reativá-la.
- **Datas da ocorrência**: `scheduled_date` (posição atual, muda ao transferir), `occurrence_date` (prevista pela regra, nunca muda), `original_date` (primeira data). `rescheduled_count` incrementa a cada mudança de dia.
- **Unicidade**: índice único `(recurring_task_id, occurrence_date)`; avulsas têm `recurring_task_id` nulo (MySQL aceita vários NULL).
- **Geração idempotente**: `gerarDoMolde` só olha para depois da última `occurrence_date` existente e usa `firstOrCreate`. Rodar duas vezes não duplica, e uma ocorrência apagada no meio da série não volta.
- **Regras de recorrência**: diária respeita `interval` (a partir de `starts_on`); semanal usa `days_of_week` (ISO 1 = segunda) e **ignora `interval`**; mensal em dia 29–31 cai no último dia dos meses curtos. `yearly` retorna `false` (não gera).
- **Editar com `aplicar_futuras`**: atualiza o molde e as futuras **pendentes**. Se `frequency`, `days_of_week` ou `day_of_month` mudaram, as futuras antigas são apagadas e o gerador recria. Se a recorrência vira "nenhuma", o molde é desativado com `ends_on = data`. Sem a flag, só a ocorrência muda; uma avulsa pode virar série.
- **Encerrar série**: molde `is_active = false`, `ends_on = hoje`, apaga as pendentes de hoje em diante; histórico fica.
- **Atrasadas**: aparecem apenas na visão de **hoje** (dia e mês). `trazerAtrasadasParaHoje` move todas as pendentes anteriores a hoje.
- **Tela Status**: barra "concluídas hoje" conta só o que está agendado para hoje; `xpHoje` é a soma líquida de `point_transactions` (moeda XP) do dia, com estornos descontando.
- **Contrato JSON do toggle**: `{ done, player: {xp, xp_max, level, gold, rank, rank_changed}, leveled_up, toast }`; `toast` é `null` ao desmarcar.

## Como testar

```bash
php artisan test --compact tests/Feature/Http/Controllers/TasksControllerTest.php
php artisan test --compact tests/Feature/MissoesFormTest.php
php artisan test --compact tests/Feature/MissoesGestaoTest.php
php artisan test --compact tests/Feature/RecurrenceServiceTest.php
php artisan test --compact tests/Feature/StatusTest.php
```

Cenários cobertos: criação avulsa/diária/semanal/mensal e validações; toggle com crédito e estorno de XP e ouro, JSON, `leveled_up`/`rank_changed`, bloqueio de cancelada; edição só desta ocorrência vs. futuras, reconstrução ao mudar a regra, avulsa → série, preservação de XP/ouro em concluída; exclusão com estorno; cancelar; transferir sem tocar na regra; atrasadas; encerrar série; filtros e as quatro visões; datas de recorrência (intervalo, dias ISO, dia 31), idempotência e o comando; Status.

Teste manual do agendador: `php artisan missoes:gerar-recorrentes --dias=7`.

## Pontos de atenção

- **Concorrência no toggle**: `concluir`/`desmarcar` não usam `lockForUpdate`, então dois cliques simultâneos poderiam lançar pontos em dobro. Só há um usuário, o risco é baixo. (Inferido do código; sem teste que cubra.)
- **Saldo duplicado**: o saldo vive em `users.xp_total`/`users.ouro` e também pode ser calculado por `PointTransaction::balance()`. Nada garante que os dois fiquem iguais, e o `max(0, …)` no estorno pode divergir do livro-razão. O comentário da migration `point_transactions` ("Saldo = SUM(amount)") está desatualizado após a coluna `currency`.
- **Nomes divergentes**: AGENTS.md cita a coluna `gold` em `users`, mas o código usa `users.ouro`.
- **Toast só mostra XP**: o toast de conclusão exibe `+{points} XP` e não o ouro.
- **Transferência sem limite**: `TaskTransferRequest` aceita qualquer data, inclusive passada.
- **Apagar a última ocorrência de uma série**: como o gerador parte da última `occurrence_date`, apagar a mais recente pode fazê-la ser recriada na próxima geração (o teste cobre apenas ocorrência apagada no meio da série). A CONFIRMAR.
- **`Frequency::Yearly` e `toRRule()`**: anual não gera ocorrências de missão; `toRRule()` referencia uma biblioteca que o módulo não usa. A CONFIRMAR se é resquício ou plano futuro.
- **`Task::scopeBetweenDates`**: o comentário cita FullCalendar, mas não há uso dentro do módulo. A CONFIRMAR.
- **Agendador em produção**: o comando depende de `schedule:run` (cron/Task Scheduler) estar configurado. A CONFIRMAR no ambiente.
- **Nomes de comandos e rotas em português** (`missoes:*`, `{missao}`) e métodos de service em português, por convenção do projeto para o que aparece na interface; os models seguem em inglês.
- **Filtros** não se aplicam à tela Status.
