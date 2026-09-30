<?php

use App\Enums\TaskRank;
use App\Enums\TaskStatus;
use App\Models\RecurringTask;
use App\Models\Task;
use App\Models\User;

describe('atrasadas para hoje', function () {
    it('redirects guests to the login page', function () {
        $this->patch(route('missoes.atrasadas-hoje'))->assertRedirect(route('login.index'));
    });

    it('moves only pending overdue tasks to today', function () {
        $this->actingAs(User::factory()->create());
        $atrasada = Task::factory()->create(['scheduled_date' => today()->subDays(3)]);
        $feita = Task::factory()->create(['scheduled_date' => today()->subDays(3), 'status' => TaskStatus::Done]);
        $futura = Task::factory()->create(['scheduled_date' => today()->addDays(2)]);

        $this->patch(route('missoes.atrasadas-hoje'))->assertRedirect(route('missoes.index'));

        expect($atrasada->refresh())->rescheduled_count->toBe(1);
        expect($atrasada->scheduled_date->isToday())->toBeTrue();
        expect($feita->refresh()->scheduled_date->isToday())->toBeFalse();
        expect($futura->refresh()->scheduled_date->isToday())->toBeFalse();
    });
});

describe('cancelar', function () {
    it('cancels a pending task without touching the balance', function () {
        $user = User::factory()->create(['xp_total' => 10, 'ouro' => 10]);
        $this->actingAs($user);
        $task = Task::factory()->create(['points' => 30]);

        $this->patch(route('missoes.cancelar', $task))->assertRedirect();

        expect($task->refresh()->status)->toBe(TaskStatus::Cancelled);
        expect($user->refresh())->xp_total->toBe(10)->ouro->toBe(10);
    });

    it('refunds xp and gold when the task was already done', function () {
        $user = User::factory()->create(['xp_total' => 100, 'ouro' => 100]);
        $this->actingAs($user);
        $task = Task::factory()->create(['points' => 30, 'status' => TaskStatus::Done, 'completed_at' => now()]);

        $this->patch(route('missoes.cancelar', $task))->assertRedirect();

        expect($task->refresh()->status)->toBe(TaskStatus::Cancelled);
        expect($user->refresh())->xp_total->toBe(70)->ouro->toBe(70);
    });
});

describe('transferir', function () {
    it('rejects done and cancelled tasks', function () {
        $this->actingAs(User::factory()->create());
        $feita = Task::factory()->create(['status' => TaskStatus::Done]);
        $cancelada = Task::factory()->create(['status' => TaskStatus::Cancelled]);

        $this->patch(route('missoes.transferir', $feita), ['data' => '2026-10-05'])->assertUnprocessable();
        $this->patch(route('missoes.transferir', $cancelada), ['data' => '2026-10-05'])->assertUnprocessable();
    });

    it('keeps the recurrence date and the rule when moving an occurrence', function () {
        $this->actingAs(User::factory()->create());
        $molde = RecurringTask::factory()->create();
        $task = Task::factory()->create([
            'recurring_task_id' => $molde->id, 'occurrence_date' => today(), 'scheduled_date' => today(),
        ]);

        $this->patch(route('missoes.transferir', $task), ['data' => '2026-10-05'])->assertRedirect();

        $task->refresh();
        expect($task->scheduled_date->toDateString())->toBe('2026-10-05');
        expect($task->occurrence_date->isToday())->toBeTrue();
        expect($task->rescheduled_count)->toBe(1);
        expect($molde->refresh()->is_active)->toBeTrue();
    });
});

describe('encerrar série', function () {
    it('deactivates the rule and removes upcoming pending occurrences only', function () {
        $this->actingAs(User::factory()->create());
        $molde = RecurringTask::factory()->create(['starts_on' => today()->subDays(5)]);
        $passada = Task::factory()->create(['recurring_task_id' => $molde->id, 'occurrence_date' => today()->subDay(), 'scheduled_date' => today()->subDay()]);
        $hoje = Task::factory()->create(['recurring_task_id' => $molde->id, 'occurrence_date' => today(), 'scheduled_date' => today()]);
        $futura = Task::factory()->create(['recurring_task_id' => $molde->id, 'occurrence_date' => today()->addDay(), 'scheduled_date' => today()->addDay()]);
        $futuraFeita = Task::factory()->create(['recurring_task_id' => $molde->id, 'occurrence_date' => today()->addDays(2), 'scheduled_date' => today()->addDays(2), 'status' => TaskStatus::Done]);

        $this->delete(route('missoes.encerrar-serie', $hoje))->assertRedirect(route('missoes.index'));

        expect($molde->refresh()->is_active)->toBeFalse();
        expect($molde->ends_on->isToday())->toBeTrue();
        expect(Task::find($passada->id))->not->toBeNull();
        expect(Task::find($hoje->id))->toBeNull();
        expect(Task::find($futura->id))->toBeNull();
        expect(Task::find($futuraFeita->id))->not->toBeNull(); // histórico de conclusão fica
    });

    it('rejects a one-off task', function () {
        $this->actingAs(User::factory()->create());
        $task = Task::factory()->create();

        $this->delete(route('missoes.encerrar-serie', $task))->assertUnprocessable();
    });
});

describe('índice com filtros e visões', function () {
    it('filters by title, status, rank and type on the day view', function () {
        $this->actingAs(User::factory()->create());
        $molde = RecurringTask::factory()->create();
        Task::factory()->create(['title' => 'Estudar Laravel', 'rank' => TaskRank::B]);
        Task::factory()->create(['title' => 'Comprar pão', 'status' => TaskStatus::Done]);
        Task::factory()->create(['title' => 'Treino diário', 'recurring_task_id' => $molde->id, 'occurrence_date' => today()]);

        $this->get(route('missoes.index', ['q' => 'laravel']))->assertSee('Estudar Laravel')->assertDontSee('Comprar pão');
        $this->get(route('missoes.index', ['status' => 'done']))->assertSee('Comprar pão')->assertDontSee('Estudar Laravel');
        $this->get(route('missoes.index', ['rank' => 'B']))->assertSee('Estudar Laravel')->assertDontSee('Treino diário');
        $this->get(route('missoes.index', ['tipo' => 'recorrente']))->assertSee('Treino diário')->assertDontSee('Estudar Laravel');
        $this->get(route('missoes.index', ['tipo' => 'avulsa']))->assertSee('Estudar Laravel')->assertDontSee('Treino diário');
    });

    it('rejects an unknown status filter', function () {
        $this->actingAs(User::factory()->create());

        $this->get(route('missoes.index', ['status' => 'xyz']))->assertSessionHasErrors('status');
    });

    it('renders every view and falls back to today on an invalid date', function (string $visao) {
        $this->actingAs(User::factory()->create());
        Task::factory()->create(['title' => 'Missão de hoje']);

        $this->get(route('missoes.index', ['v' => $visao, 'data' => '2026-02-31']))->assertOk();
    })->with(['dia', 'semana', 'mes', 'ano']);

    it('shows overdue tasks on today but not on other days', function () {
        $this->actingAs(User::factory()->create());
        Task::factory()->create(['title' => 'Ficou para trás', 'scheduled_date' => today()->subDays(2)]);

        $this->get(route('missoes.index'))->assertSee('Ficou para trás')->assertSee('Trazer todas para hoje');
        $this->get(route('missoes.index', ['data' => today()->addDay()->toDateString()]))->assertDontSee('Ficou para trás');
    });
});
