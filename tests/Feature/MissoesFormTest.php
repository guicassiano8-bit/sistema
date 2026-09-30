<?php

use App\Enums\Frequency;
use App\Enums\TaskRank;
use App\Enums\TaskStatus;
use App\Models\RecurringTask;
use App\Models\Task;
use App\Models\User;

function dadosDoForm(array $extra = []): array
{
    return array_merge([
        'completo' => 1,
        'titulo' => 'Treinar',
        'descricao' => 'Peito e costas',
        'data' => '2026-10-01', // quinta-feira
        'horario' => '07:30',
        'xp' => 50,
        'ouro' => 20,
        'rank' => 'B',
        'recorrencia' => 'nenhuma',
    ], $extra);
}

describe('create', function () {
    it('redirects guests to the login page', function () {
        $this->get(route('missoes.create'))->assertRedirect(route('login.index'));
    });

    it('renders the form, prefilled by the quick modal query string', function () {
        $this->actingAs(User::factory()->create());

        $this->get(route('missoes.create', ['titulo' => 'Ler', 'xp' => 50, 'data' => '2026-10-02', 'recorrencia' => 'diaria']))
            ->assertOk()
            ->assertSee('Aceitar missão')
            ->assertSee('value="Ler"', false)
            ->assertSee('value="2026-10-02"', false);
    });

    it('creates a one-off task with the full payload', function () {
        $this->actingAs(User::factory()->create());

        $this->post(route('missoes.store'), dadosDoForm())
            ->assertRedirect(route('missoes.index', ['data' => '2026-10-01']));

        $task = Task::sole();
        expect($task->title)->toBe('Treinar');
        expect($task->description)->toBe('Peito e costas');
        expect($task->points)->toBe(50);
        expect($task->gold)->toBe(20);
        expect($task->rank)->toBe(TaskRank::B);
        expect(substr($task->start_time, 0, 5))->toBe('07:30');
        expect($task->recurring_task_id)->toBeNull();
    });

    it('creates a weekly rule with the chosen ISO weekdays', function () {
        $this->actingAs(User::factory()->create());

        $this->post(route('missoes.store'), dadosDoForm([
            'recorrencia' => 'semanal',
            'recorrencia_dias' => [7, 1, 3],
            'termina_em' => '2026-12-31',
        ]))->assertRedirect();

        $molde = RecurringTask::sole();
        expect($molde->frequency)->toBe(Frequency::Weekly);
        expect($molde->days_of_week)->toBe([1, 3, 7]);
        expect($molde->ends_on->toDateString())->toBe('2026-12-31');
        expect($molde->gold)->toBe(20);
        expect(Task::where('recurring_task_id', $molde->id)->count())->toBe(Task::count());
    });

    it('creates a monthly rule with the day of the month', function () {
        $this->actingAs(User::factory()->create());

        $this->post(route('missoes.store'), dadosDoForm(['recorrencia' => 'mensal', 'recorrencia_dia_mes' => 15]))
            ->assertRedirect();

        $molde = RecurringTask::sole();
        expect($molde->frequency)->toBe(Frequency::Monthly);
        expect($molde->day_of_month)->toBe(15);
        expect($molde->days_of_week)->toBeNull();
    });

    it('validates the recurrence fields in the default error bag', function () {
        $this->actingAs(User::factory()->create());

        $this->post(route('missoes.store'), dadosDoForm(['recorrencia' => 'semanal']))
            ->assertSessionHasErrors('recorrencia_dias');

        $this->post(route('missoes.store'), dadosDoForm(['recorrencia' => 'mensal']))
            ->assertSessionHasErrors('recorrencia_dia_mes');

        $this->post(route('missoes.store'), dadosDoForm(['recorrencia' => 'diaria', 'termina_em' => '2026-09-01']))
            ->assertSessionHasErrors('termina_em');

        $this->post(route('missoes.store'), dadosDoForm(['rank' => 'Z']))
            ->assertSessionHasErrors('rank');

        expect(Task::count())->toBe(0);
    });
});

describe('edit and update', function () {
    it('redirects guests to the login page', function () {
        $task = Task::factory()->create();

        $this->get(route('missoes.edit', $task))->assertRedirect(route('login.index'));
        $this->put(route('missoes.update', $task), dadosDoForm())->assertRedirect(route('login.index'));
    });

    it('renders the form filled with the task and its recurrence rule', function () {
        $this->actingAs(User::factory()->create());
        $molde = RecurringTask::factory()->create(['frequency' => Frequency::Weekly, 'days_of_week' => [2, 4]]);
        $task = Task::factory()->create(['recurring_task_id' => $molde->id, 'occurrence_date' => today(), 'title' => 'Corrida']);

        $this->get(route('missoes.edit', $task))
            ->assertOk()
            ->assertSee('Salvar missão')
            ->assertSee('value="Corrida"', false)
            ->assertSee('name="aplicar_futuras"', false)
            ->assertSee('name="recorrencia_dias[]" value="2" class="peer sr-only" checked', false);
    });

    it('updates only this occurrence and leaves the rule alone', function () {
        $this->actingAs(User::factory()->create());
        $molde = RecurringTask::factory()->create(['title' => 'Regra', 'points' => 10]);
        $task = Task::factory()->create([
            'recurring_task_id' => $molde->id,
            'occurrence_date' => '2026-10-01',
            'scheduled_date' => '2026-10-01',
        ]);

        $this->put(route('missoes.update', $task), dadosDoForm(['data' => '2026-10-03']))->assertRedirect();

        $task->refresh();
        expect($task->title)->toBe('Treinar');
        expect($task->points)->toBe(50);
        expect($task->scheduled_date->toDateString())->toBe('2026-10-03');
        expect($task->occurrence_date->toDateString())->toBe('2026-10-01');
        expect($task->rescheduled_count)->toBe(1);

        $molde->refresh();
        expect($molde->title)->toBe('Regra');
        expect($molde->points)->toBe(10);
    });

    it('applies changes to the rule and to upcoming pending occurrences when asked', function () {
        $this->actingAs(User::factory()->create());
        $molde = RecurringTask::factory()->create();
        $atual = Task::factory()->create(['recurring_task_id' => $molde->id, 'occurrence_date' => '2026-10-01', 'scheduled_date' => '2026-10-01']);
        $futura = Task::factory()->create(['recurring_task_id' => $molde->id, 'occurrence_date' => '2026-10-02', 'scheduled_date' => '2026-10-02']);
        $passada = Task::factory()->create(['recurring_task_id' => $molde->id, 'occurrence_date' => '2026-09-30', 'scheduled_date' => '2026-09-30', 'title' => 'Antiga']);

        // mesma regra (diária): só os campos mudam
        $this->put(route('missoes.update', $atual), dadosDoForm([
            'aplicar_futuras' => 1,
            'recorrencia' => 'diaria',
        ]))->assertRedirect();

        expect($molde->refresh())->title->toBe('Treinar')->frequency->toBe(Frequency::Daily);
        expect($futura->refresh()->title)->toBe('Treinar');
        expect($futura->points)->toBe(50);
        expect($passada->refresh()->title)->toBe('Antiga');
    });

    it('rebuilds upcoming occurrences when the rule itself changes', function () {
        $this->actingAs(User::factory()->create());
        $molde = RecurringTask::factory()->create(['starts_on' => '2026-09-01']);
        $atual = Task::factory()->create(['recurring_task_id' => $molde->id, 'occurrence_date' => '2026-10-01', 'scheduled_date' => '2026-10-01']);
        $futura = Task::factory()->create(['recurring_task_id' => $molde->id, 'occurrence_date' => '2026-10-02', 'scheduled_date' => '2026-10-02']);

        $this->put(route('missoes.update', $atual), dadosDoForm([
            'aplicar_futuras' => 1,
            'recorrencia' => 'semanal',
            'recorrencia_dias' => [4],
        ]))->assertRedirect();

        expect($molde->refresh())->frequency->toBe(Frequency::Weekly)->days_of_week->toBe([4]);
        expect(Task::find($futura->id))->toBeNull(); // sexta-feira não pertence mais à regra
        expect($molde->tasks()->whereDate('scheduled_date', '>', '2026-10-01')->get()
            ->every(fn ($t) => $t->scheduled_date->isoWeekday() === 4))->toBeTrue();
    });

    it('turns a one-off task into a recurring one', function () {
        $this->actingAs(User::factory()->create());
        $task = Task::factory()->create(['scheduled_date' => '2026-10-01', 'original_date' => '2026-10-01']);

        $this->put(route('missoes.update', $task), dadosDoForm(['recorrencia' => 'diaria']))->assertRedirect();

        $molde = RecurringTask::sole();
        $task->refresh();
        expect($task->recurring_task_id)->toBe($molde->id);
        expect($task->occurrence_date->toDateString())->toBe('2026-10-01');
    });

    it('keeps xp and gold of a completed task so a later refund stays exact', function () {
        $this->actingAs(User::factory()->create());
        $task = Task::factory()->create(['points' => 30, 'gold' => 30, 'status' => TaskStatus::Done, 'completed_at' => now()]);

        $this->put(route('missoes.update', $task), dadosDoForm(['data' => today()->toDateString()]))->assertRedirect();

        $task->refresh();
        expect($task->title)->toBe('Treinar');
        expect($task->points)->toBe(30);
        expect($task->gold)->toBe(30);
    });
});

describe('destroy', function () {
    it('redirects guests to the login page', function () {
        $task = Task::factory()->create();

        $this->delete(route('missoes.destroy', $task))->assertRedirect(route('login.index'));
    });

    it('deletes a pending task', function () {
        $this->actingAs(User::factory()->create());
        $task = Task::factory()->create();

        $this->delete(route('missoes.destroy', $task))->assertRedirect();

        expect(Task::count())->toBe(0);
    });

    it('refunds xp and gold of a completed task before deleting it', function () {
        $user = User::factory()->create(['xp_total' => 100, 'ouro' => 100]);
        $this->actingAs($user);
        $task = Task::factory()->create(['points' => 30, 'gold' => 10, 'status' => TaskStatus::Done, 'completed_at' => now()]);

        $this->delete(route('missoes.destroy', $task))->assertRedirect();

        $user->refresh();
        expect($user->xp_total)->toBe(70);
        expect($user->ouro)->toBe(90);
        expect(Task::count())->toBe(0);
    });

    it('keeps the recurrence rule when deleting one occurrence', function () {
        $this->actingAs(User::factory()->create());
        $molde = RecurringTask::factory()->create();
        $task = Task::factory()->create(['recurring_task_id' => $molde->id, 'occurrence_date' => today()]);

        $this->delete(route('missoes.destroy', $task))->assertRedirect();

        expect(RecurringTask::count())->toBe(1);
    });
});

describe('gold on completion', function () {
    it('credits the task gold instead of its xp', function () {
        $user = User::factory()->create(['xp_total' => 0, 'ouro' => 0]);
        $this->actingAs($user);
        $task = Task::factory()->create(['points' => 50, 'gold' => 5]);

        $this->patch(route('missoes.toggle', $task))->assertRedirect();
        $user->refresh();
        expect($user->xp_total)->toBe(50);
        expect($user->ouro)->toBe(5);

        $this->patch(route('missoes.toggle', $task))->assertRedirect();
        $user->refresh();
        expect($user->xp_total)->toBe(0);
        expect($user->ouro)->toBe(0);
    });
});
