<?php

use App\Enums\Frequency;
use App\Enums\PointCurrency;
use App\Enums\PointTransactionType;
use App\Enums\TaskStatus;
use App\Models\PointTransaction;
use App\Models\RecurringTask;
use App\Models\Task;
use App\Models\User;

describe('store', function () {
    it('redirects guests to the login page', function () {
        $this->post(route('missoes.store'), ['titulo' => 'Ler 20 páginas'])
            ->assertRedirect(route('login.index'));
    });

    it('creates a one-off task when no recurrence is chosen', function () {
        $this->actingAs(User::factory()->create());

        $this->post(route('missoes.store'), [
            'titulo' => 'Ler 20 páginas',
            'xp' => 30,
            'data' => '2026-10-01',
            'recorrencia' => '',
        ])->assertRedirect();

        $task = Task::sole();
        expect($task->title)->toBe('Ler 20 páginas');
        expect($task->points)->toBe(30);
        expect($task->scheduled_date->toDateString())->toBe('2026-10-01');
        expect($task->original_date->toDateString())->toBe('2026-10-01');
        expect($task->occurrence_date)->toBeNull();
        expect($task->recurring_task_id)->toBeNull();
        expect($task->status)->toBe(TaskStatus::Pending);
    });

    it('creates a daily recurring task and its first occurrence', function () {
        $this->actingAs(User::factory()->create());

        $this->post(route('missoes.store'), [
            'titulo' => 'Beber água',
            'xp' => 10,
            'data' => '2026-10-01',
            'recorrencia' => 'diaria',
        ])->assertRedirect();

        $recurringTask = RecurringTask::sole();
        expect($recurringTask->title)->toBe('Beber água');
        expect($recurringTask->points)->toBe(10);
        expect($recurringTask->frequency)->toBe(Frequency::Daily);
        expect($recurringTask->days_of_week)->toBeNull();
        expect($recurringTask->starts_on->toDateString())->toBe('2026-10-01');

        $task = Task::sole();
        expect($task->recurring_task_id)->toBe($recurringTask->id);
        expect($task->occurrence_date->toDateString())->toBe('2026-10-01');
    });

    it('creates a weekly recurring task tied to the scheduled weekday', function () {
        $this->actingAs(User::factory()->create());

        // 2026-10-01 é quinta-feira (ISO 4)
        $this->post(route('missoes.store'), [
            'titulo' => 'Revisar tarefas',
            'xp' => 50,
            'data' => '2026-10-01',
            'recorrencia' => 'semanal',
        ])->assertRedirect();

        $recurringTask = RecurringTask::sole();
        expect($recurringTask->frequency)->toBe(Frequency::Weekly);
        expect($recurringTask->days_of_week)->toBe([4]);
    });

    it('rejects an empty title', function () {
        $this->actingAs(User::factory()->create());

        $this->post(route('missoes.store'), [
            'titulo' => '',
            'xp' => 30,
            'data' => '2026-10-01',
            'recorrencia' => '',
        ])->assertSessionHasErrors('titulo', errorBag: 'missaoRapida');

        expect(Task::count())->toBe(0);
    });

    it('rejects an xp value outside the fixed options', function () {
        $this->actingAs(User::factory()->create());

        $this->post(route('missoes.store'), [
            'titulo' => 'Ler 20 páginas',
            'xp' => 999,
            'data' => '2026-10-01',
            'recorrencia' => '',
        ])->assertSessionHasErrors('xp', errorBag: 'missaoRapida');
    });

    it('rejects an invalid recurrence option', function () {
        $this->actingAs(User::factory()->create());

        $this->post(route('missoes.store'), [
            'titulo' => 'Ler 20 páginas',
            'xp' => 30,
            'data' => '2026-10-01',
            'recorrencia' => 'mensal',
        ])->assertSessionHasErrors('recorrencia', errorBag: 'missaoRapida');
    });
});

describe('toggle', function () {
    it('redirects guests to the login page', function () {
        $task = Task::factory()->create();

        $this->patch(route('missoes.toggle', $task))
            ->assertRedirect(route('login.index'));
    });

    it('completes a pending task and credits xp and gold', function () {
        $user = User::factory()->create(['xp_total' => 0, 'ouro' => 0]);
        $this->actingAs($user);
        $task = Task::factory()->create(['points' => 30, 'status' => TaskStatus::Pending]);

        $this->patch(route('missoes.toggle', $task))->assertRedirect();

        $task->refresh();
        expect($task->status)->toBe(TaskStatus::Done);
        expect($task->completed_at)->not->toBeNull();

        $user->refresh();
        expect($user->xp_total)->toBe(30);
        expect($user->ouro)->toBe(30);

        $lancamentos = PointTransaction::query()->where('source_id', $task->id)->get();
        expect($lancamentos)->toHaveCount(2);
        expect($lancamentos->pluck('currency')->all())->toEqualCanonicalizing([PointCurrency::Xp, PointCurrency::Gold]);
        $lancamentos->each(function (PointTransaction $lancamento) {
            expect($lancamento->type)->toBe(PointTransactionType::TaskCompleted);
            expect($lancamento->amount)->toBe(30);
        });
    });

    it('reverts a completed task, refunding xp and gold', function () {
        $user = User::factory()->create(['xp_total' => 100, 'ouro' => 100]);
        $this->actingAs($user);
        $task = Task::factory()->create(['points' => 30, 'status' => TaskStatus::Done, 'completed_at' => now()]);

        $this->patch(route('missoes.toggle', $task))->assertRedirect();

        $task->refresh();
        expect($task->status)->toBe(TaskStatus::Pending);
        expect($task->completed_at)->toBeNull();

        $user->refresh();
        expect($user->xp_total)->toBe(70);
        expect($user->ouro)->toBe(70);

        $lancamentos = PointTransaction::query()->where('source_id', $task->id)->get();
        expect($lancamentos)->toHaveCount(2);
        $lancamentos->each(function (PointTransaction $lancamento) {
            expect($lancamento->type)->toBe(PointTransactionType::TaskReverted);
            expect($lancamento->amount)->toBe(-30);
        });
    });

    it('returns the player payload as json when the request expects json', function () {
        $user = User::factory()->create(['xp_total' => 0, 'ouro' => 0]);
        $this->actingAs($user);
        $task = Task::factory()->create(['points' => 30, 'status' => TaskStatus::Pending]);

        $this->patchJson(route('missoes.toggle', $task))
            ->assertOk()
            ->assertJson([
                'done' => true,
                'player' => ['xp' => 30, 'xp_max' => 100, 'level' => 1, 'gold' => 30, 'rank' => 'E', 'rank_changed' => false],
                'leveled_up' => false,
                'toast' => ['type' => 'success', 'title' => 'Missão concluída', 'message' => $task->title, 'value' => '+30 XP'],
            ]);
    });

    it('reports leveled_up and rank_changed when a completion crosses a level/rank boundary', function () {
        // soma de xpParaProximoNivel(1..9) = 100+200+...+900 = 4500 → nível 9 termina em 4500 - 1
        $user = User::factory()->create(['xp_total' => 4499, 'ouro' => 0]);
        $this->actingAs($user);
        $task = Task::factory()->create(['points' => 1, 'status' => TaskStatus::Pending]);

        $this->patchJson(route('missoes.toggle', $task))
            ->assertOk()
            ->assertJson([
                'player' => ['level' => 10, 'rank' => 'D', 'rank_changed' => true],
                'leveled_up' => true,
            ]);
    });

    it('blocks toggling a cancelled task', function () {
        $this->actingAs(User::factory()->create());
        $task = Task::factory()->create(['status' => TaskStatus::Cancelled]);

        $this->patch(route('missoes.toggle', $task))->assertStatus(422);
    });
});

describe('transferir', function () {
    it('redirects guests to the login page', function () {
        $task = Task::factory()->create();

        $this->patch(route('missoes.transferir', $task), ['data' => '2026-10-05'])
            ->assertRedirect(route('login.index'));
    });

    it('moves the task to another day without touching the recurrence rule', function () {
        $this->actingAs(User::factory()->create());
        $recurringTask = RecurringTask::factory()->create();
        $task = Task::factory()->create([
            'recurring_task_id' => $recurringTask->id,
            'occurrence_date' => '2026-10-01',
            'scheduled_date' => '2026-10-01',
        ]);

        $this->patch(route('missoes.transferir', $task), ['data' => '2026-10-05'])->assertRedirect();

        $task->refresh();
        expect($task->scheduled_date->toDateString())->toBe('2026-10-05');
        expect($task->occurrence_date->toDateString())->toBe('2026-10-01');
        expect($task->rescheduled_count)->toBe(1);
        expect($task->recurring_task_id)->toBe($recurringTask->id);
    });

    it('rejects a missing or invalid date', function () {
        $this->actingAs(User::factory()->create());
        $task = Task::factory()->create();

        $this->patch(route('missoes.transferir', $task), ['data' => 'não é uma data'])
            ->assertSessionHasErrors('data');
    });
});
