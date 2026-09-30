<?php

namespace App\Services;

use App\Enums\Frequency;
use App\Enums\TaskStatus;
use App\Models\RecurringTask;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Transforma a regra (RecurringTask) em ocorrências reais (Task).
 * Calculamos as datas aqui em vez de instalar uma biblioteca de RRULE: as três
 * frequências de missão (diária, semanal, mensal) cabem em poucas linhas.
 */
class RecurrenceService
{
    /** Quantos dias à frente o sistema mantém ocorrências já criadas. */
    public const JANELA_PADRAO_DIAS = 30;

    /**
     * Datas em que a regra cai dentro do intervalo, respeitando starts_on e ends_on.
     *
     * @return Collection<int, Carbon>
     */
    public function datasNoIntervalo(RecurringTask $molde, Carbon $de, Carbon $ate): Collection
    {
        $inicio = $de->copy()->startOfDay()->max($molde->starts_on->copy()->startOfDay());
        $fim = $ate->copy()->startOfDay();

        if ($molde->ends_on) {
            $fim = $fim->min($molde->ends_on->copy()->startOfDay());
        }

        if ($inicio->gt($fim)) {
            return collect();
        }

        return collect(CarbonPeriod::create($inicio, $fim))
            ->filter(fn (Carbon $dia) => $this->ocorreEm($molde, $dia))
            ->values();
    }

    /**
     * Cria as ocorrências que ainda não existem, até $ate. Só olha para depois da
     * última ocorrência já criada: assim uma ocorrência apagada de propósito não volta
     * no dia seguinte e rodar o comando duas vezes não duplica nada.
     *
     * @return int quantidade de missões criadas
     */
    public function gerar(?Carbon $ate = null, ?RecurringTask $molde = null): int
    {
        $ate ??= today()->addDays(self::JANELA_PADRAO_DIAS);

        $moldes = $molde
            ? collect([$molde])
            : RecurringTask::query()->where('is_active', true)->get();

        return $moldes->sum(fn (RecurringTask $regra) => $this->gerarDoMolde($regra, $ate));
    }

    private function gerarDoMolde(RecurringTask $molde, Carbon $ate): int
    {
        $ultima = $molde->tasks()->max('occurrence_date');

        $de = today()->max($molde->starts_on);
        if ($ultima) {
            $de = $de->max(Carbon::parse($ultima)->addDay());
        }

        $criadas = 0;

        foreach ($this->datasNoIntervalo($molde, $de, $ate) as $dia) {
            $ocorrencia = $molde->tasks()->firstOrCreate(
                ['occurrence_date' => $dia],
                [
                    'title' => $molde->title,
                    'description' => $molde->description,
                    'points' => $molde->points,
                    'gold' => $molde->gold,
                    'rank' => $molde->rank,
                    'start_time' => $molde->start_time,
                    'end_time' => $molde->end_time,
                    'scheduled_date' => $dia,
                    'original_date' => $dia,
                    'status' => TaskStatus::Pending,
                ],
            );

            if ($ocorrencia->wasRecentlyCreated) {
                $criadas++;
            }
        }

        return $criadas;
    }

    private function ocorreEm(RecurringTask $molde, Carbon $dia): bool
    {
        return match ($molde->frequency) {
            Frequency::Daily => (int) abs($molde->starts_on->diffInDays($dia)) % max(1, $molde->interval) === 0,
            Frequency::Weekly => in_array($dia->isoWeekday(), $molde->days_of_week ?? [], true),
            // dia 29–31 em mês curto cai no último dia do mês
            Frequency::Monthly => $dia->day === min($molde->day_of_month ?? $molde->starts_on->day, $dia->daysInMonth),
            default => false,
        };
    }
}
