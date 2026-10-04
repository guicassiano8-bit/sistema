<?php

namespace App\Services;

use App\Enums\Frequency;
use App\Enums\PointCurrency;
use App\Enums\PointTransactionType;
use App\Enums\ShoppingStatus;
use App\Enums\TaskRank;
use App\Enums\TaskStatus;
use App\Models\RecurringTask;
use App\Models\ShoppingItem;
use App\Models\Task;
use App\Models\User;
use App\Support\Jogador;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TasksService
{
    public const VISOES = ['dia', 'semana', 'mes', 'ano'];

    public function __construct(private RecurrenceService $recorrencia) {}

    /** Lê "2026-09-23" da URL. Vazio ou inválido → hoje. */
    public function dataDaTela(?string $valor): Carbon
    {
        if (! $valor) {
            return today();
        }

        try {
            $data = Carbon::createFromFormat('!Y-m-d', $valor);
        } catch (\Throwable) {
            return today();
        }

        // barra datas que "transbordam": 2026-02-31 viraria 3 de março
        return $data->format('Y-m-d') === $valor ? $data : today();
    }

    /** Para onde as setas ‹ › levam, conforme a visão. */
    public function navegacao(string $visao, Carbon $data): array
    {
        return match ($visao) {
            'dia' => [$data->copy()->subDay(),               $data->copy()->addDay()],
            'semana' => [$data->copy()->subWeek(),              $data->copy()->addWeek()],
            'mes' => [$data->copy()->subMonthNoOverflow(),   $data->copy()->addMonthNoOverflow()],
            'ano' => [$data->copy()->subYearNoOverflow(),    $data->copy()->addYearNoOverflow()],
        };
    }

    /** Intervalo de datas que a visão mostra. */
    public function periodo(string $visao, Carbon $data): array
    {
        return match ($visao) {
            'dia' => [$data->copy(), $data->copy()],
            'semana' => [$data->copy()->startOfWeek(Carbon::SUNDAY), $data->copy()->endOfWeek(Carbon::SATURDAY)],
            // o calendário mostra dias do mês anterior/seguinte para completar as semanas
            'mes' => [$data->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY),
                $data->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY)],
            'ano' => [$data->copy()->startOfYear(), $data->copy()->endOfYear()],
        };
    }

    /** Missões de um dia (+ atrasadas, se o dia for hoje). */
    public function missoesDoDia(Builder $missoes, Carbon $data)
    {
        return (clone $missoes)
            ->where(function ($q) use ($data) {
                $q->whereDate('scheduled_date', $data);
                if ($data->isToday()) {
                    $q->orWhere(fn ($q) => $q->whereDate('scheduled_date', '<', $data)->where('status', TaskStatus::Pending));
                }
            })
            ->orderByRaw("status = '".TaskStatus::Done->value."'")
            ->orderBy('start_time')
            ->get();
    }

    /** 7 dias, cada um com suas missões — formato que a visão Semana espera. */
    public function semana(Builder $missoes, Carbon $inicio, Carbon $fim)
    {
        $porDia = (clone $missoes)
            ->whereBetween('scheduled_date', [$inicio->toDateString(), $fim->toDateString()])
            ->orderByRaw("status = '".TaskStatus::Done->value."'")
            ->orderBy('start_time')
            ->get()
            ->groupBy(fn ($m) => $m->scheduled_date->format('Y-m-d'));

        return collect(CarbonPeriod::create($inicio, $fim))->map(fn (Carbon $d) => [
            'data' => $d,
            'missoes' => $porDia->get($d->format('Y-m-d'), collect()),
        ]);
    }

    /** ['2026-09-23' => ['total' => 7, 'feitas' => 3], ...] — um COUNT no banco, sem carregar as missões. */
    public function resumoPorDia(Builder $missoes, Carbon $inicio, Carbon $fim): array
    {
        return (clone $missoes)
            ->whereBetween('scheduled_date', [$inicio->toDateString(), $fim->toDateString()])
            ->selectRaw("scheduled_date, COUNT(*) as total, SUM(status = '".TaskStatus::Done->value."') as feitas")
            ->groupBy('scheduled_date')
            ->toBase()          // devolve linhas simples (data vem como texto "Y-m-d")
            ->get()
            ->mapWithKeys(fn ($r) => [
                substr($r->scheduled_date, 0, 10) => ['total' => (int) $r->total, 'feitas' => (int) $r->feitas],
            ])
            ->all();
    }

    /**
     * Filtros opcionais da listagem: q (título), status, rank e tipo (avulsa|recorrente).
     *
     * @param  array{q?: ?string, status?: ?string, rank?: ?string, tipo?: ?string}  $filtros
     */
    public function aplicarFiltros(Builder $missoes, array $filtros): Builder
    {
        return $missoes
            ->when($filtros['q'] ?? null, fn ($q, $busca) => $q->where('title', 'like', '%'.addcslashes($busca, '%_\\').'%'))
            ->when($filtros['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filtros['rank'] ?? null, fn ($q, $rank) => $q->where('rank', $rank))
            ->when($filtros['tipo'] ?? null, fn ($q, $tipo) => $tipo === 'recorrente'
                ? $q->whereNotNull('recurring_task_id')
                : $q->whereNull('recurring_task_id'));
    }

    /** Monta os dados da tela de missões conforme a visão selecionada. */
    public function dadosDaTela(string $visao, Carbon $data, array $filtros = []): array
    {
        [$anterior, $proxima] = $this->navegacao($visao, $data);
        [$inicio, $fim] = $this->periodo($visao, $data);

        // consulta, ainda sem ir ao banco; os filtros valem para as quatro visões
        $missoes = $this->aplicarFiltros(Task::query()->with(['recurringTask', 'shoppingItems']), $filtros);

        $extras = match ($visao) {
            'dia' => ['missoes' => $this->missoesDoDia($missoes, $data)],
            'semana' => ['dias' => $this->semana($missoes, $inicio, $fim)],
            'mes' => [
                'resumoMes' => $this->resumoPorDia($missoes, $inicio, $fim),
                'missoes' => $this->missoesDoDia($missoes, $data),
            ],
            'ano' => ['resumoAno' => $this->resumoPorDia($missoes, $inicio, $fim)],
        };

        return compact('anterior', 'proxima') + $extras;
    }

    /**
     * Cria uma missão a partir dos dados validados (modal rápido ou form completo).
     * Sem recorrência, cria a Task avulsa. Com recorrência, cria o molde em
     * RecurringTask e a primeira ocorrência em Task, numa única transação.
     *
     * @param  array<string, mixed>  $dados  titulo, xp, data, recorrencia e, no form completo, os demais campos
     */
    public function criar(array $dados): Task
    {
        $data = Carbon::createFromFormat('!Y-m-d', $dados['data']);
        $campos = $this->camposDaMissao($dados);

        if (! $this->temRecorrencia($dados)) {
            return Task::create($campos + [
                'scheduled_date' => $data,
                'original_date' => $data,
                'status' => TaskStatus::Pending,
            ]);
        }

        return DB::transaction(function () use ($dados, $data, $campos) {
            $recurringTask = RecurringTask::create($campos + $this->regraDeRecorrencia($dados, $data) + [
                'interval' => 1,
                'starts_on' => $data,
                'is_active' => true,
            ]);

            $primeira = $recurringTask->tasks()->create($campos + [
                'scheduled_date' => $data,
                'occurrence_date' => $data,
                'original_date' => $data,
                'status' => TaskStatus::Pending,
            ]);

            $this->recorrencia->gerar(molde: $recurringTask);

            return $primeira;
        });
    }

    /**
     * Edita uma missão. Por padrão só esta ocorrência muda; a regra de
     * recorrência só é tocada com $aplicarFuturas. Numa missão concluída,
     * XP e ouro ficam intactos para o estorno devolver exatamente o que foi concedido.
     *
     * @param  array<string, mixed>  $dados
     */
    public function atualizar(Task $missao, array $dados, bool $aplicarFuturas = false): Task
    {
        $data = Carbon::createFromFormat('!Y-m-d', $dados['data']);
        $campos = $this->camposDaMissao($dados);

        if ($missao->is_done) {
            unset($campos['points'], $campos['gold']);
        }

        return DB::transaction(function () use ($missao, $dados, $data, $campos, $aplicarFuturas) {
            if (! $data->isSameDay($missao->scheduled_date)) {
                $campos['scheduled_date'] = $data;
                $campos['rescheduled_count'] = $missao->rescheduled_count + 1;
            }

            $molde = $missao->recurringTask;

            if ($molde && $aplicarFuturas) {
                $this->aplicarNoMolde($molde, $missao, $dados, $data);
            } elseif (! $molde && $this->temRecorrencia($dados)) {
                $molde = RecurringTask::create($this->camposDaMissao($dados) + $this->regraDeRecorrencia($dados, $data) + [
                    'interval' => 1,
                    'starts_on' => $data,
                    'is_active' => true,
                ]);
                $campos['recurring_task_id'] = $molde->id;
                $campos['occurrence_date'] = $data;
            }

            $missao->update($campos);

            // itens do Inventário acompanham a data da missão "Fazer Compras"
            if (isset($campos['scheduled_date'])) {
                $missao->shoppingItems()->update(['scheduled_date' => $data]);
            }

            if ($molde) {
                $this->recorrencia->gerar(molde: $molde);
            }

            return $missao;
        });
    }

    /** Cancela a missão (sai do fluxo sem valer XP). Se estava concluída, estorna antes. */
    public function cancelar(Task $missao, User $user): void
    {
        DB::transaction(function () use ($missao, $user) {
            if ($missao->is_done) {
                $this->desmarcar($missao, $user);
            }

            $missao->update(['status' => TaskStatus::Cancelled]);
        });
    }

    /** Traz todas as pendentes de dias anteriores para hoje. Devolve quantas foram movidas. */
    public function trazerAtrasadasParaHoje(): int
    {
        return DB::transaction(function () {
            $atrasadas = Task::query()->overdue()->pluck('id');

            ShoppingItem::query()->whereIn('task_id', $atrasadas)->update(['scheduled_date' => today()]);

            return Task::query()->whereIn('id', $atrasadas)->update([
                'scheduled_date' => today(),
                'rescheduled_count' => DB::raw('rescheduled_count + 1'),
            ]);
        });
    }

    /**
     * Encerra a série: a regra para de gerar e as ocorrências pendentes de hoje em diante
     * somem. O que já foi concluído ou ficou no passado permanece como histórico.
     */
    public function encerrarSerie(Task $missao): void
    {
        $molde = $missao->recurringTask;

        abort_if($molde === null, 422, 'Esta missão não faz parte de uma série.');

        DB::transaction(function () use ($molde) {
            $molde->update(['is_active' => false, 'ends_on' => today()]);

            $molde->tasks()->pending()->whereDate('scheduled_date', '>=', today())->delete();
        });
    }

    /** Apaga a missão; se estava concluída, estorna XP e ouro antes. */
    public function excluir(Task $missao, User $user): void
    {
        DB::transaction(function () use ($missao, $user) {
            if ($missao->is_done) {
                $this->desmarcar($missao, $user);
            }

            // sem a missão, os itens deixam de ter data de compra
            $missao->shoppingItems()->update(['task_id' => null, 'scheduled_date' => null]);

            $missao->delete();
        });
    }

    /** Atualiza o molde e as próximas ocorrências pendentes (as passadas e concluídas ficam como estão). */
    private function aplicarNoMolde(RecurringTask $molde, Task $missao, array $dados, Carbon $data): void
    {
        $campos = $this->camposDaMissao($dados);

        if (! $this->temRecorrencia($dados)) {
            $molde->update(['is_active' => false, 'ends_on' => $data]);

            return;
        }

        $molde->update($campos + $this->regraDeRecorrencia($dados, $data));

        $futuras = $molde->tasks()
            ->pending()
            ->where('id', '!=', $missao->id)
            ->whereDate('scheduled_date', '>', $missao->scheduled_date);

        // regra nova (dias/frequência): as futuras antigas não valem mais, o gerador recria no novo padrão
        if ($molde->wasChanged(['frequency', 'days_of_week', 'day_of_month'])) {
            $futuras->delete();

            return;
        }

        $futuras->update($campos);
    }

    /** Campos comuns à Task e ao molde. O ouro segue o XP quando não vem informado (modal rápido). */
    private function camposDaMissao(array $dados): array
    {
        return [
            'title' => $dados['titulo'],
            'description' => $dados['descricao'] ?? null,
            'points' => $dados['xp'],
            'gold' => $dados['ouro'] ?? $dados['xp'],
            'rank' => $dados['rank'] ?? TaskRank::E->value,
            'start_time' => $dados['horario'] ?? null,
        ];
    }

    private function temRecorrencia(array $dados): bool
    {
        return ! in_array($dados['recorrencia'] ?? null, [null, '', 'nenhuma'], true);
    }

    /** Traduz o que o form envia para as colunas de recurring_tasks (dias em ISO: 1 = segunda … 7 = domingo). */
    private function regraDeRecorrencia(array $dados, Carbon $data): array
    {
        $frequencia = match ($dados['recorrencia']) {
            'semanal' => Frequency::Weekly,
            'mensal' => Frequency::Monthly,
            default => Frequency::Daily,
        };

        $dias = collect($dados['recorrencia_dias'] ?? [$data->isoWeekday()])->map(fn ($d) => (int) $d)->unique()->sort()->values()->all();

        return [
            'frequency' => $frequencia,
            'days_of_week' => $frequencia === Frequency::Weekly ? $dias : null,
            'day_of_month' => $frequencia === Frequency::Monthly ? (int) ($dados['recorrencia_dia_mes'] ?? $data->day) : null,
            'ends_on' => $dados['termina_em'] ?? null,
        ];
    }

    /**
     * Marca/desmarca a conclusão de uma missão, concedendo ou estornando
     * XP ($missao->points) e ouro ($missao->gold).
     *
     * @return array{done: bool, jogador: Jogador, leveled_up: bool, rank_changed: bool}
     */
    public function alternarConclusao(Task $missao, User $user): array
    {
        abort_if($missao->status === TaskStatus::Cancelled, 422, 'Missão cancelada não pode ser alternada.');

        return DB::transaction(function () use ($missao, $user) {
            $resultado = $missao->is_done
                ? $this->desmarcar($missao, $user)
                : $this->concluir($missao, $user);

            // "Fazer Compras": concluir marca todos os itens; desfazer devolve todos a pendente
            $this->aplicarNosItens($missao, $resultado['done']);

            return $resultado;
        });
    }

    /**
     * Mantém a conclusão da missão "Fazer Compras" coerente com os itens: pendente com todos os
     * itens comprados → conclui; concluída com algum item pendente → estorna.
     *
     * @return array{done: bool, jogador: Jogador, leveled_up: bool, rank_changed: bool}|null null se nada mudou
     */
    public function sincronizarConclusaoComItens(Task $missao, User $user): ?array
    {
        if ($missao->status === TaskStatus::Cancelled || ! $missao->shoppingItems()->exists()) {
            return null;
        }

        $temPendente = $missao->shoppingItems()->where('status', ShoppingStatus::Pending->value)->exists();

        if ($missao->status === TaskStatus::Pending && ! $temPendente) {
            return $this->concluir($missao, $user);
        }

        if ($missao->is_done && $temPendente) {
            return $this->desmarcar($missao, $user);
        }

        return null;
    }

    private function aplicarNosItens(Task $missao, bool $comprados): void
    {
        $missao->shoppingItems()
            ->where('status', ($comprados ? ShoppingStatus::Pending : ShoppingStatus::Purchased)->value)
            ->update([
                'status' => ($comprados ? ShoppingStatus::Purchased : ShoppingStatus::Pending)->value,
                'purchased_at' => $comprados ? now() : null,
            ]);
    }

    /** Muda a data de uma ocorrência sem alterar a regra de recorrência. */
    public function transferir(Task $missao, string $data): void
    {
        abort_unless($missao->status === TaskStatus::Pending, 422, 'Só missões pendentes podem ser transferidas.');

        $novaData = Carbon::createFromFormat('!Y-m-d', $data);

        DB::transaction(function () use ($missao, $novaData) {
            $missao->update([
                'scheduled_date' => $novaData,
                'rescheduled_count' => $missao->rescheduled_count + 1,
            ]);

            $missao->shoppingItems()->update(['scheduled_date' => $novaData]);
        });
    }

    private function concluir(Task $missao, User $user): array
    {
        return DB::transaction(function () use ($missao, $user) {
            $antes = LevelService::calcular($user->xp_total);

            $missao->update(['status' => TaskStatus::Done, 'completed_at' => now()]);
            $this->lancarPontos($missao, PointTransactionType::TaskCompleted, $missao->points, $missao->gold);

            $user->xp_total += $missao->points;
            $user->ouro += $missao->gold;
            $user->save();

            return $this->resultado(true, $antes, $user);
        });
    }

    private function desmarcar(Task $missao, User $user): array
    {
        return DB::transaction(function () use ($missao, $user) {
            $antes = LevelService::calcular($user->xp_total);

            $missao->update(['status' => TaskStatus::Pending, 'completed_at' => null]);
            $this->lancarPontos($missao, PointTransactionType::TaskReverted, -$missao->points, -$missao->gold);

            $user->xp_total = max(0, $user->xp_total - $missao->points);
            $user->ouro = max(0, $user->ouro - $missao->gold);
            $user->save();

            return $this->resultado(false, $antes, $user);
        });
    }

    /** Um lançamento de XP e outro de ouro, sempre em par (mesmo com ouro 0, para manter a auditoria simétrica). */
    private function lancarPontos(Task $missao, PointTransactionType $type, int $xp, int $ouro): void
    {
        foreach ([[PointCurrency::Xp, $xp], [PointCurrency::Gold, $ouro]] as [$moeda, $amount]) {
            $missao->pointTransactions()->create([
                'amount' => $amount,
                'type' => $type,
                'currency' => $moeda,
            ]);
        }
    }

    private function resultado(bool $done, array $antes, User $user): array
    {
        $depois = LevelService::calcular($user->xp_total);

        return [
            'done' => $done,
            'jogador' => $user->jogador(),
            'leveled_up' => $depois['nivel'] > $antes['nivel'],
            'rank_changed' => $depois['rank'] !== $antes['rank'],
        ];
    }
}
