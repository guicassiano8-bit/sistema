<?php

namespace App\Services;

use App\Enums\Frequency;
use App\Enums\PointCurrency;
use App\Enums\PointTransactionType;
use App\Enums\TaskStatus;
use App\Models\RecurringTask;
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

    /** Monta os dados da tela de missões conforme a visão selecionada. */
    public function dadosDaTela(string $visao, Carbon $data): array
    {
        [$anterior, $proxima] = $this->navegacao($visao, $data);
        [$inicio, $fim] = $this->periodo($visao, $data);

        $missoes = Task::query()->with('recurringTask'); // consulta, ainda sem ir ao banco

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
     * Cria uma missão a partir dos dados validados do modal "Nova missão".
     * Sem recorrência, cria a Task avulsa. Com recorrência, cria o molde em
     * RecurringTask e a primeira ocorrência em Task, numa única transação.
     *
     * @param  array{titulo: string, xp: int, data: string, recorrencia: ?string}  $dados
     */
    public function criar(array $dados): Task
    {
        $data = Carbon::createFromFormat('!Y-m-d', $dados['data']);

        if (! $dados['recorrencia']) {
            return Task::create([
                'title' => $dados['titulo'],
                'points' => $dados['xp'],
                'scheduled_date' => $data,
                'original_date' => $data,
                'status' => TaskStatus::Pending,
            ]);
        }

        return DB::transaction(function () use ($dados, $data) {
            $recurringTask = RecurringTask::create([
                'title' => $dados['titulo'],
                'points' => $dados['xp'],
                'frequency' => $dados['recorrencia'] === 'semanal' ? Frequency::Weekly : Frequency::Daily,
                'interval' => 1,
                'days_of_week' => $dados['recorrencia'] === 'semanal' ? [$data->isoWeekday()] : null,
                'starts_on' => $data,
                'is_active' => true,
            ]);

            return $recurringTask->tasks()->create([
                'title' => $dados['titulo'],
                'points' => $dados['xp'],
                'scheduled_date' => $data,
                'occurrence_date' => $data,
                'original_date' => $data,
                'status' => TaskStatus::Pending,
            ]);
        });
    }

    /**
     * Marca/desmarca a conclusão de uma missão, concedendo ou estornando
     * XP e ouro (o valor de ambos é o mesmo: $missao->points).
     *
     * @return array{done: bool, jogador: Jogador, leveled_up: bool, rank_changed: bool}
     */
    public function alternarConclusao(Task $missao, User $user): array
    {
        abort_if($missao->status === TaskStatus::Cancelled, 422, 'Missão cancelada não pode ser alternada.');

        return $missao->is_done
            ? $this->desmarcar($missao, $user)
            : $this->concluir($missao, $user);
    }

    /** Muda a data de uma ocorrência sem alterar a regra de recorrência. */
    public function transferir(Task $missao, string $data): void
    {
        $missao->update([
            'scheduled_date' => Carbon::createFromFormat('!Y-m-d', $data),
            'rescheduled_count' => $missao->rescheduled_count + 1,
        ]);
    }

    private function concluir(Task $missao, User $user): array
    {
        return DB::transaction(function () use ($missao, $user) {
            $antes = LevelService::calcular($user->xp_total);

            $missao->update(['status' => TaskStatus::Done, 'completed_at' => now()]);
            $this->lancarPontos($missao, PointTransactionType::TaskCompleted, $missao->points);

            $user->xp_total += $missao->points;
            $user->ouro += $missao->points;
            $user->save();

            return $this->resultado(true, $antes, $user);
        });
    }

    private function desmarcar(Task $missao, User $user): array
    {
        return DB::transaction(function () use ($missao, $user) {
            $antes = LevelService::calcular($user->xp_total);

            $missao->update(['status' => TaskStatus::Pending, 'completed_at' => null]);
            $this->lancarPontos($missao, PointTransactionType::TaskReverted, -$missao->points);

            $user->xp_total = max(0, $user->xp_total - $missao->points);
            $user->ouro = max(0, $user->ouro - $missao->points);
            $user->save();

            return $this->resultado(false, $antes, $user);
        });
    }

    /** Um lançamento de XP e outro de ouro, sempre em par. */
    private function lancarPontos(Task $missao, PointTransactionType $type, int $amount): void
    {
        foreach (PointCurrency::cases() as $moeda) {
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
