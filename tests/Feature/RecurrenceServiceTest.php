<?php

use App\Enums\Frequency;
use App\Models\RecurringTask;
use App\Models\Task;
use App\Services\RecurrenceService;
use Carbon\Carbon;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-09-29 10:00')); // terça-feira
    $this->service = app(RecurrenceService::class);
});

function datas($colecao): array
{
    return $colecao->map(fn ($d) => $d->toDateString())->all();
}

describe('datasNoIntervalo', function () {
    it('returns every day for a daily rule', function () {
        $molde = RecurringTask::factory()->create(['starts_on' => '2026-09-29']);

        expect(datas($this->service->datasNoIntervalo($molde, Carbon::parse('2026-09-29'), Carbon::parse('2026-10-01'))))
            ->toBe(['2026-09-29', '2026-09-30', '2026-10-01']);
    });

    it('honors the interval on a daily rule', function () {
        $molde = RecurringTask::factory()->create(['starts_on' => '2026-09-29', 'interval' => 2]);

        expect(datas($this->service->datasNoIntervalo($molde, Carbon::parse('2026-09-29'), Carbon::parse('2026-10-03'))))
            ->toBe(['2026-09-29', '2026-10-01', '2026-10-03']);
    });

    it('returns only the chosen ISO weekdays for a weekly rule', function () {
        $molde = RecurringTask::factory()->create([
            'frequency' => Frequency::Weekly, 'days_of_week' => [1, 4], 'starts_on' => '2026-09-01',
        ]);

        // segunda = 1, quinta = 4
        expect(datas($this->service->datasNoIntervalo($molde, Carbon::parse('2026-09-28'), Carbon::parse('2026-10-05'))))
            ->toBe(['2026-09-28', '2026-10-01', '2026-10-05']);
    });

    it('falls back to the last day of short months for a monthly rule on day 31', function () {
        $molde = RecurringTask::factory()->create([
            'frequency' => Frequency::Monthly, 'day_of_month' => 31, 'starts_on' => '2026-01-01',
        ]);

        expect(datas($this->service->datasNoIntervalo($molde, Carbon::parse('2026-01-01'), Carbon::parse('2026-04-30'))))
            ->toBe(['2026-01-31', '2026-02-28', '2026-03-31', '2026-04-30']);
    });

    it('never returns dates before starts_on or after ends_on', function () {
        $molde = RecurringTask::factory()->create(['starts_on' => '2026-10-02', 'ends_on' => '2026-10-03']);

        expect(datas($this->service->datasNoIntervalo($molde, Carbon::parse('2026-09-29'), Carbon::parse('2026-10-10'))))
            ->toBe(['2026-10-02', '2026-10-03']);
    });
});

describe('gerar', function () {
    it('creates the upcoming occurrences with the rule fields', function () {
        $molde = RecurringTask::factory()->create(['title' => 'Beber água', 'points' => 10, 'starts_on' => '2026-09-29']);

        $criadas = $this->service->gerar(Carbon::parse('2026-10-01'));

        expect($criadas)->toBe(3);
        $tarefa = Task::whereDate('occurrence_date', '2026-09-30')->sole();
        expect($tarefa)->recurring_task_id->toBe($molde->id)->title->toBe('Beber água')->points->toBe(10);
        expect($tarefa->scheduled_date->toDateString())->toBe('2026-09-30');
        expect($tarefa->original_date->toDateString())->toBe('2026-09-30');
    });

    it('is idempotent', function () {
        RecurringTask::factory()->create(['starts_on' => '2026-09-29']);

        $this->service->gerar(Carbon::parse('2026-10-01'));
        $segunda = $this->service->gerar(Carbon::parse('2026-10-01'));

        expect($segunda)->toBe(0);
        expect(Task::count())->toBe(3);
    });

    it('does not recreate an occurrence that was deleted', function () {
        RecurringTask::factory()->create(['starts_on' => '2026-09-29']);
        $this->service->gerar(Carbon::parse('2026-10-03'));

        Task::whereDate('occurrence_date', '2026-09-30')->delete();
        $this->service->gerar(Carbon::parse('2026-10-03'));

        expect(Task::whereDate('occurrence_date', '2026-09-30')->exists())->toBeFalse();
    });

    it('keeps generating after a transferred occurrence', function () {
        $molde = RecurringTask::factory()->create(['starts_on' => '2026-09-29']);
        $this->service->gerar(Carbon::parse('2026-09-30'));
        Task::whereDate('occurrence_date', '2026-09-29')->update(['scheduled_date' => '2026-10-05']);

        $this->service->gerar(Carbon::parse('2026-10-02'));

        // a transferida mantém occurrence_date; nenhuma duplicata do dia 29
        expect(Task::whereDate('occurrence_date', '2026-09-29')->count())->toBe(1);
        expect(Task::whereDate('occurrence_date', '2026-10-02')->exists())->toBeTrue();
    });

    it('skips inactive rules', function () {
        RecurringTask::factory()->create(['starts_on' => '2026-09-29', 'is_active' => false]);

        expect($this->service->gerar(Carbon::parse('2026-10-05')))->toBe(0);
    });
});

describe('missoes:gerar-recorrentes', function () {
    it('generates occurrences for the given window', function () {
        RecurringTask::factory()->create(['starts_on' => '2026-09-29']);

        $this->artisan('missoes:gerar-recorrentes', ['--dias' => 2])->assertSuccessful();

        expect(Task::count())->toBe(3); // hoje + 2 dias
    });
});
