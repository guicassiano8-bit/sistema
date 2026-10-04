<?php

namespace App\Services;

use App\Enums\PointCurrency;
use App\Models\PointTransaction;
use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;

class StatusService
{
    public function __construct(private TasksService $tasks) {}

    /**
     * Dados da tela Status.
     *
     * @return array{missoesHoje: Collection<int, Task>, hojeFeitas: int, hojeTotal: int, xpHoje: int}
     */
    public function dadosDaTela(): array
    {
        $hoje = today();
        $missoesHoje = $this->tasks->missoesDoDia(Task::query()->with(['recurringTask', 'shoppingItems']), $hoje);

        // atrasadas aparecem na lista, mas não entram na barra de "concluídas hoje"
        $agendadasHoje = $missoesHoje->filter(fn (Task $m) => $m->scheduled_date->isSameDay($hoje));

        return [
            'missoesHoje' => $missoesHoje,
            'hojeTotal' => $agendadasHoje->count(),
            'hojeFeitas' => $agendadasHoje->filter->is_done->count(),
            'xpHoje' => $this->xpDoDia(),
        ];
    }

    /** Soma líquida: o estorno de uma conclusão entra negativo e desconta do total. */
    private function xpDoDia(): int
    {
        return (int) PointTransaction::query()
            ->where('currency', PointCurrency::Xp)
            ->whereDate('created_at', today())
            ->sum('amount');
    }
}
