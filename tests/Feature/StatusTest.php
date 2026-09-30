<?php

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;

it('redirects guests to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login.index'));
});

it('counts only tasks scheduled for today in the progress bar', function () {
    $this->actingAs($user = User::factory()->create());

    Task::factory()->count(2)->create();
    Task::factory()->create(['status' => TaskStatus::Done]);
    Task::factory()->create(['scheduled_date' => today()->subDays(2)]); // atrasada

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertViewHas('hojeTotal', 3)
        ->assertViewHas('hojeFeitas', 1)
        ->assertViewHas('missoesHoje', fn ($missoes) => $missoes->count() === 4);
});

it('reflects xp earned today and drops it when the completion is undone', function () {
    $this->actingAs($user = User::factory()->create());
    $missao = Task::factory()->create(['points' => 50, 'gold' => 20]);

    $this->patchJson(route('missoes.toggle', $missao));
    $this->get(route('dashboard'))->assertViewHas('xpHoje', 50);

    $this->patchJson(route('missoes.toggle', $missao));
    $this->get(route('dashboard'))->assertViewHas('xpHoje', 0);
});
