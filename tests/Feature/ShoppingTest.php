<?php

use App\Enums\ShoppingCategory;
use App\Enums\ShoppingStatus;
use App\Enums\TaskStatus;
use App\Models\PointTransaction;
use App\Models\ShoppingItem;
use App\Models\Task;
use App\Models\Transaction;
use App\Models\User;

describe('acesso', function () {
    it('redirects guests to the login page', function () {
        $item = ShoppingItem::factory()->create();

        $this->get(route('inventario.index'))->assertRedirect(route('login.index'));
        $this->patch(route('inventario.toggle', $item))->assertRedirect(route('login.index'));
        $this->delete(route('inventario.limpar'))->assertRedirect(route('login.index'));
    });

    it('lists pending items by rarity and the purchased ones', function () {
        $this->actingAs(User::factory()->create());
        ShoppingItem::factory()->urgent()->create(['name' => 'Remédio']);
        ShoppingItem::factory()->purchased()->create(['name' => 'Pão']);

        $this->get(route('inventario.index'))->assertOk()
            ->assertSeeInOrder(['Lendário', 'Remédio'])
            ->assertSee('Pão');
    });
});

describe('cadastro', function () {
    it('creates an item and remembers the last rarity', function () {
        $this->actingAs(User::factory()->create());

        $this->post(route('inventario.store'), ['name' => 'Arroz', 'category' => 'important', 'quantity' => 2, 'unit' => 'kg'])
            ->assertRedirect(route('inventario.index'))
            ->assertSessionHas('ultima_categoria', 'important');

        $item = ShoppingItem::sole();
        expect($item->name)->toBe('Arroz')
            ->and($item->category)->toBe(ShoppingCategory::Important)
            ->and($item->quantity)->toBe(2)
            ->and($item->unit)->toBe('kg')
            ->and($item->status)->toBe(ShoppingStatus::Pending);
    });

    it('validates the name and the rarity in the inventario bag', function () {
        $this->actingAs(User::factory()->create());

        $this->post(route('inventario.store'), ['name' => '', 'category' => 'lendario'])
            ->assertSessionHasErrorsIn('inventario', ['name', 'category']);

        expect(ShoppingItem::count())->toBe(0);
    });

    it('updates and deletes an item', function () {
        $this->actingAs(User::factory()->create());
        $item = ShoppingItem::factory()->create(['name' => 'Leite']);

        $this->put(route('inventario.update', $item), ['name' => 'Leite integral', 'category' => 'urgent', 'quantity' => 3])
            ->assertRedirect(route('inventario.index'));
        expect($item->refresh())->name->toBe('Leite integral')->category->toBe(ShoppingCategory::Urgent);

        $this->delete(route('inventario.destroy', $item))->assertRedirect(route('inventario.index'));
        expect(ShoppingItem::count())->toBe(0);
    });
});

describe('toggle', function () {
    it('marks as purchased and answers JSON', function () {
        $this->actingAs(User::factory()->create(['ouro' => 10, 'xp_total' => 10]));
        $item = ShoppingItem::factory()->create();

        $this->patchJson(route('inventario.toggle', $item))->assertOk()->assertExactJson(['done' => true]);

        expect($item->refresh()->status)->toBe(ShoppingStatus::Purchased)
            ->and($item->purchased_at)->not->toBeNull()
            ->and(PointTransaction::count())->toBe(0)
            ->and(Transaction::count())->toBe(0);
    });

    it('puts a purchased item back on the list and redirects without JSON', function () {
        $this->actingAs(User::factory()->create());
        $item = ShoppingItem::factory()->purchased()->create();

        $this->from(route('inventario.index'))->patch(route('inventario.toggle', $item))
            ->assertRedirect(route('inventario.index'));

        expect($item->refresh()->status)->toBe(ShoppingStatus::Pending)->and($item->purchased_at)->toBeNull();
    });
});

describe('limpar adquiridos', function () {
    it('removes only the purchased items', function () {
        $this->actingAs(User::factory()->create());
        ShoppingItem::factory()->purchased()->count(2)->create();
        $pendente = ShoppingItem::factory()->create();

        $this->delete(route('inventario.limpar'))->assertRedirect(route('inventario.index'));

        expect(ShoppingItem::sole()->is($pendente))->toBeTrue();
    });
});

describe('badge de navegação', function () {
    it('counts only pending urgent items', function () {
        $this->actingAs(User::factory()->create());
        ShoppingItem::factory()->urgent()->count(2)->create();
        ShoppingItem::factory()->urgent()->purchased()->create();
        ShoppingItem::factory()->create();

        $this->get(route('inventario.index'))->assertSee('2<span class="sr-only"> pendentes', false);
    });
});

describe('missão "Fazer Compras"', function () {
    function itemParaComprarEm(string $data, array $extra = []): ShoppingItem
    {
        $item = ShoppingItem::factory()->create($extra);

        test()->put(route('inventario.update', $item), [
            'name' => $item->name, 'category' => 'daily', 'quantity' => 1, 'scheduled_date' => $data,
        ])->assertSessionHasNoErrors();

        return $item->refresh();
    }

    it('creates a 10 XP mission without gold when an item gets a date', function () {
        $this->actingAs(User::factory()->create());

        $item = itemParaComprarEm('2026-10-10');

        $missao = Task::sole();
        expect($missao)->title->toBe('Fazer Compras')
            ->points->toBe(10)->gold->toBe(0)
            ->scheduled_date->toDateString()->toBe('2026-10-10')
            ->status->toBe(TaskStatus::Pending)
            ->recurring_task_id->toBeNull()
            ->and($item->task_id)->toBe($missao->id);
    });

    it('also creates the mission when the item is added with a date', function () {
        $this->actingAs(User::factory()->create());

        $this->post(route('inventario.store'), ['name' => 'Café', 'category' => 'daily', 'scheduled_date' => '2026-10-11']);

        expect(Task::sole()->scheduled_date->toDateString())->toBe('2026-10-11')
            ->and(ShoppingItem::sole()->task_id)->toBe(Task::sole()->id);
    });

    it('shares one mission between items of the same day', function () {
        $this->actingAs(User::factory()->create());

        itemParaComprarEm('2026-10-10');
        itemParaComprarEm('2026-10-10');

        expect(Task::count())->toBe(1)->and(Task::sole()->shoppingItems)->toHaveCount(2);
    });

    it('moves the link and drops the old mission when the date changes', function () {
        $this->actingAs(User::factory()->create());
        $item = itemParaComprarEm('2026-10-10');

        $this->put(route('inventario.update', $item), ['name' => $item->name, 'category' => 'daily', 'scheduled_date' => '2026-10-15']);

        expect(Task::sole()->scheduled_date->toDateString())->toBe('2026-10-15')
            ->and($item->refresh()->task_id)->toBe(Task::sole()->id);
    });

    it('keeps the mission when the date is unchanged', function () {
        $this->actingAs(User::factory()->create());
        $item = itemParaComprarEm('2026-10-10');
        $missaoId = Task::sole()->id;

        $this->put(route('inventario.update', $item), ['name' => 'Outro nome', 'category' => 'urgent', 'scheduled_date' => '2026-10-10']);

        expect(Task::sole()->id)->toBe($missaoId)->and($item->refresh()->task_id)->toBe($missaoId);
    });

    it('removes an empty pending mission when the date is cleared or the item is deleted', function () {
        $this->actingAs(User::factory()->create());
        $item = itemParaComprarEm('2026-10-10');

        $this->put(route('inventario.update', $item), ['name' => $item->name, 'category' => 'daily', 'scheduled_date' => null]);
        expect(Task::count())->toBe(0)->and($item->refresh()->task_id)->toBeNull();

        $outro = itemParaComprarEm('2026-10-12');
        $this->delete(route('inventario.destroy', $outro));
        expect(Task::count())->toBe(0);
    });

    it('keeps a completed mission when it becomes empty', function () {
        $this->actingAs(User::factory()->create());
        $item = itemParaComprarEm('2026-10-10');
        Task::query()->update(['status' => TaskStatus::Done]);

        $this->put(route('inventario.update', $item), ['name' => $item->name, 'category' => 'daily', 'scheduled_date' => null]);

        expect(Task::sole()->status)->toBe(TaskStatus::Done);
    });

    it('keeps the mission while other items still need it', function () {
        $this->actingAs(User::factory()->create());
        $item = itemParaComprarEm('2026-10-10');
        itemParaComprarEm('2026-10-10');

        $this->delete(route('inventario.destroy', $item));

        expect(Task::count())->toBe(1);
    });

    it('follows the mission when it is transferred and detaches the items when it is deleted', function () {
        $this->actingAs(User::factory()->create());
        $item = itemParaComprarEm('2026-10-10');
        $missao = Task::sole();

        $this->patch(route('missoes.transferir', $missao), ['data' => '2026-10-12']);
        expect($item->refresh()->scheduled_date->toDateString())->toBe('2026-10-12');

        $this->delete(route('missoes.destroy', $missao));
        expect($item->refresh())->task_id->toBeNull()->scheduled_date->toBeNull();
    });

    it('lists the items of the day inside the mission card', function () {
        $this->actingAs(User::factory()->create());
        // o toast da última edição repete o nome do item, então o de outra data vai primeiro
        itemParaComprarEm('2026-10-20', ['name' => 'Item de outra data']);
        itemParaComprarEm('2026-10-10', ['name' => 'Sabão em pó']);
        itemParaComprarEm('2026-10-10', ['name' => 'Detergente']);

        $this->get(route('missoes.index', ['data' => '2026-10-10']))->assertOk()
            ->assertSee('Fazer Compras')->assertSee('2 itens')
            ->assertSee('Sabão em pó')->assertSee('Detergente')->assertDontSee('Item de outra data');
    });

    it('rejects an invalid date in the inventario bag', function () {
        $this->actingAs(User::factory()->create());
        $item = ShoppingItem::factory()->create();

        $this->put(route('inventario.update', $item), ['name' => 'X', 'category' => 'daily', 'scheduled_date' => '10/10/2026'])
            ->assertSessionHasErrorsIn('inventario', ['scheduled_date']);
    });
});

describe('conclusão da missão x itens', function () {
    it('marks every item as purchased when the mission is completed and reverts them when it is undone', function () {
        $user = User::factory()->create(['xp_total' => 0, 'ouro' => 0]);
        $this->actingAs($user);
        itemParaComprarEm('2026-10-10');
        itemParaComprarEm('2026-10-10');
        $missao = Task::sole();

        $this->patchJson(route('missoes.toggle', $missao))->assertOk()->assertJsonPath('done', true);

        expect(ShoppingItem::where('status', ShoppingStatus::Purchased)->count())->toBe(2)
            ->and(ShoppingItem::whereNull('purchased_at')->count())->toBe(0)
            ->and($user->refresh()->xp_total)->toBe(10);

        $this->patchJson(route('missoes.toggle', $missao))->assertOk()->assertJsonPath('done', false);

        expect(ShoppingItem::where('status', ShoppingStatus::Pending)->count())->toBe(2)
            ->and(ShoppingItem::whereNotNull('purchased_at')->count())->toBe(0)
            ->and($user->refresh()->xp_total)->toBe(0);
    });

    it('completes the mission when the last pending item is purchased', function () {
        $user = User::factory()->create(['xp_total' => 0]);
        $this->actingAs($user);
        $primeiro = itemParaComprarEm('2026-10-10');
        $ultimo = itemParaComprarEm('2026-10-10');

        $this->patchJson(route('inventario.toggle', $primeiro))->assertOk()->assertExactJson(['done' => true]);
        expect(Task::sole()->status)->toBe(TaskStatus::Pending)->and($user->refresh()->xp_total)->toBe(0);

        $this->patchJson(route('inventario.toggle', $ultimo))->assertOk()
            ->assertJsonPath('done', true)->assertJsonPath('mission.done', true)
            ->assertJsonPath('toast.title', 'Missão concluída')->assertJsonStructure(['player', 'leveled_up']);

        expect(Task::sole()->status)->toBe(TaskStatus::Done)
            ->and($user->refresh()->xp_total)->toBe(10)
            ->and(PointTransaction::count())->toBe(2);
    });

    it('reverts the completed mission when one item goes back to pending', function () {
        $user = User::factory()->create(['xp_total' => 0]);
        $this->actingAs($user);
        $item = itemParaComprarEm('2026-10-10');
        $this->patchJson(route('inventario.toggle', $item));
        expect(Task::sole()->status)->toBe(TaskStatus::Done);

        $this->patchJson(route('inventario.toggle', $item))->assertOk()
            ->assertJsonPath('done', false)->assertJsonPath('mission.done', false);

        expect(Task::sole()->status)->toBe(TaskStatus::Pending)->and($user->refresh()->xp_total)->toBe(0);
    });

    it('leaves a cancelled mission alone', function () {
        $this->actingAs(User::factory()->create(['xp_total' => 0]));
        $item = itemParaComprarEm('2026-10-10');
        Task::query()->update(['status' => TaskStatus::Cancelled]);

        $this->patchJson(route('inventario.toggle', $item))->assertOk()->assertExactJson(['done' => true]);

        expect(Task::sole()->status)->toBe(TaskStatus::Cancelled);
    });
});

describe('remoção do último item pendente', function () {
    it('completes the mission when the last pending item is deleted', function () {
        $user = User::factory()->create(['xp_total' => 0]);
        $this->actingAs($user);
        $comprado = itemParaComprarEm('2026-10-10');
        $pendente = itemParaComprarEm('2026-10-10');
        $this->patchJson(route('inventario.toggle', $comprado));
        expect(Task::sole()->status)->toBe(TaskStatus::Pending);

        $this->delete(route('inventario.destroy', $pendente))
            ->assertRedirect(route('inventario.index'))
            ->assertSessionHas('sys_toast.title', 'Missão concluída');

        expect(Task::sole()->status)->toBe(TaskStatus::Done)
            ->and($user->refresh()->xp_total)->toBe(10)
            ->and(ShoppingItem::sole()->is($comprado))->toBeTrue();
    });

    it('completes the mission when the date of the last pending item is cleared', function () {
        $user = User::factory()->create(['xp_total' => 0]);
        $this->actingAs($user);
        $comprado = itemParaComprarEm('2026-10-10');
        $pendente = itemParaComprarEm('2026-10-10');
        $this->patchJson(route('inventario.toggle', $comprado));

        $this->put(route('inventario.update', $pendente), ['name' => $pendente->name, 'category' => 'daily', 'scheduled_date' => null])
            ->assertSessionHas('sys_toast.title', 'Missão concluída');

        expect(Task::sole()->status)->toBe(TaskStatus::Done)
            ->and($user->refresh()->xp_total)->toBe(10)
            ->and($pendente->refresh()->task_id)->toBeNull();
    });

    it('keeps the mission pending while another item is still pending', function () {
        $user = User::factory()->create(['xp_total' => 0]);
        $this->actingAs($user);
        $comprado = itemParaComprarEm('2026-10-10');
        $excluido = itemParaComprarEm('2026-10-10');
        itemParaComprarEm('2026-10-10');
        $this->patchJson(route('inventario.toggle', $comprado));

        $this->delete(route('inventario.destroy', $excluido));

        expect(Task::sole()->status)->toBe(TaskStatus::Pending)->and($user->refresh()->xp_total)->toBe(0);
    });
});
