<?php

use App\Enums\PointCurrency;
use App\Enums\PointTransactionType;
use App\Models\PointTransaction;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\User;
use App\Services\RewardService;

describe('acesso', function () {
    it('redirects guests to the login page', function () {
        $this->get(route('recompensas.index'))->assertRedirect(route('login.index'));
        $this->post(route('recompensas.trocar', Reward::factory()->create()))->assertRedirect(route('login.index'));
    });

    it('lists the store and the history tabs', function () {
        $this->actingAs(User::factory()->create(['ouro' => 500]));
        $reward = Reward::factory()->create(['name' => 'Jantar fora']);

        $this->get(route('recompensas.index'))->assertOk()->assertSee('Jantar fora');

        $this->post(route('recompensas.trocar', $reward));
        $this->get(route('recompensas.index', ['aba' => 'historico']))->assertOk()->assertSee('Jantar fora');
    });
});

describe('resgate', function () {
    it('debits gold, freezes the cost and writes the ledger', function () {
        $user = User::factory()->create(['ouro' => 500, 'xp_total' => 40]);
        $this->actingAs($user);
        $reward = Reward::factory()->create(['cost' => 300]);

        $this->post(route('recompensas.trocar', $reward))
            ->assertRedirect(route('recompensas.index'))
            ->assertSessionHas('ouro_anterior', 500);

        expect($user->refresh())->ouro->toBe(200)->xp_total->toBe(40);

        $resgate = RewardRedemption::sole();
        expect($resgate->cost_paid)->toBe(300);

        $lancamento = PointTransaction::sole();
        expect($lancamento->amount)->toBe(-300)
            ->and($lancamento->currency)->toBe(PointCurrency::Gold)
            ->and($lancamento->type)->toBe(PointTransactionType::RewardRedeemed)
            ->and($lancamento->source->is($resgate))->toBeTrue();
    });

    it('blocks the redemption on the back-end when gold is not enough', function () {
        $user = User::factory()->create(['ouro' => 299]);
        $this->actingAs($user);
        $reward = Reward::factory()->create(['cost' => 300]);

        $this->post(route('recompensas.trocar', $reward))->assertSessionHasErrors('recompensa');

        expect($user->refresh()->ouro)->toBe(299);
        expect(RewardRedemption::count())->toBe(0)->and(PointTransaction::count())->toBe(0);
    });

    it('blocks inactive rewards', function () {
        $user = User::factory()->create(['ouro' => 1000]);
        $this->actingAs($user);
        $reward = Reward::factory()->inactive()->create(['cost' => 100]);

        $this->post(route('recompensas.trocar', $reward))->assertSessionHasErrors('recompensa');

        expect($user->refresh()->ouro)->toBe(1000);
    });

    it('lets a one-time reward be redeemed only once', function () {
        $user = User::factory()->create(['ouro' => 1000]);
        $this->actingAs($user);
        $reward = Reward::factory()->single()->create(['cost' => 100]);

        $this->post(route('recompensas.trocar', $reward))->assertSessionDoesntHaveErrors();
        $this->post(route('recompensas.trocar', $reward))->assertSessionHasErrors('recompensa');

        expect($user->refresh()->ouro)->toBe(900);
        expect(RewardRedemption::count())->toBe(1);
    });

    it('lets a repeatable reward be redeemed again', function () {
        $user = User::factory()->create(['ouro' => 1000]);
        $this->actingAs($user);
        $reward = Reward::factory()->create(['cost' => 100]);

        $this->post(route('recompensas.trocar', $reward));
        $this->post(route('recompensas.trocar', $reward));

        expect($user->refresh()->ouro)->toBe(800);
    });

    it('keeps the history cost when the price changes later', function () {
        $this->actingAs(User::factory()->create(['ouro' => 500]));
        $reward = Reward::factory()->create(['cost' => 100]);

        $this->post(route('recompensas.trocar', $reward));
        $reward->update(['cost' => 400]);

        expect(RewardRedemption::sole()->cost_paid)->toBe(100);
    });
});

describe('desfazer resgate', function () {
    it('refunds the frozen cost and deletes the redemption and its ledger entry', function () {
        $user = User::factory()->create(['ouro' => 500, 'xp_total' => 40]);
        $this->actingAs($user);
        $reward = Reward::factory()->create(['cost' => 100]);
        $this->post(route('recompensas.trocar', $reward));
        $reward->update(['cost' => 400]);
        $user->refresh(); // o resgate mexeu no banco; na requisição real o usuário já vem fresco

        $this->delete(route('recompensas.desfazer', RewardRedemption::sole()))
            ->assertRedirect(route('recompensas.index', ['aba' => 'historico']))
            ->assertSessionHas('ouro_anterior', 400);

        expect($user->refresh())->ouro->toBe(500)->xp_total->toBe(40);
        expect(RewardRedemption::count())->toBe(0)->and(PointTransaction::count())->toBe(0);
    });

    it('brings a one-time reward back to the store', function () {
        $this->actingAs(User::factory()->create(['ouro' => 500]));
        $reward = Reward::factory()->single()->create(['name' => 'Viagem', 'cost' => 100]);
        $this->post(route('recompensas.trocar', $reward));
        expect(app(RewardService::class)->listar(1000))->toHaveCount(0);

        $this->delete(route('recompensas.desfazer', RewardRedemption::sole()));

        expect(app(RewardService::class)->listar(1000))->toHaveCount(1);
    });

    it('redirects guests to the login page', function () {
        $reward = Reward::factory()->create();
        $resgate = $reward->redemptions()->create(['cost_paid' => 10, 'redeemed_at' => now()]);

        $this->delete(route('recompensas.desfazer', $resgate))->assertRedirect(route('login.index'));

        expect(RewardRedemption::count())->toBe(1);
    });
});

describe('cadastro', function () {
    it('creates a reward', function () {
        $this->actingAs(User::factory()->create());

        $this->post(route('recompensas.store'), ['name' => 'Cinema', 'cost' => 200, 'rank' => 'B', 'is_repeatable' => '0'])
            ->assertRedirect(route('recompensas.index'));

        expect(Reward::sole())->name->toBe('Cinema')->cost->toBe(200)->is_repeatable->toBeFalse();
    });

    it('reports errors on the recompensa bag', function () {
        $this->actingAs(User::factory()->create());

        $this->post(route('recompensas.store'), ['name' => '', 'cost' => 0, 'rank' => 'B'])
            ->assertSessionHasErrorsIn('recompensa', ['name', 'cost']);

        expect(Reward::count())->toBe(0);
    });

    it('renders the edit form', function () {
        $this->actingAs(User::factory()->create());
        $reward = Reward::factory()->create(['name' => 'Massagem']);

        $this->get(route('recompensas.edit', $reward))->assertOk()->assertSee('Massagem');
    });

    it('updates a reward and can deactivate it', function () {
        $this->actingAs(User::factory()->create());
        $reward = Reward::factory()->create();

        $this->put(route('recompensas.update', $reward), ['name' => 'Novo', 'cost' => 50, 'rank' => 'S', 'is_repeatable' => '1'])
            ->assertRedirect(route('recompensas.index'));

        expect($reward->refresh())->name->toBe('Novo')->cost->toBe(50)->is_active->toBeFalse();
    });

    it('deletes a never-redeemed reward', function () {
        $this->actingAs(User::factory()->create());
        $reward = Reward::factory()->create();

        $this->delete(route('recompensas.destroy', $reward))->assertRedirect(route('recompensas.index'));

        expect(Reward::count())->toBe(0);
    });

    it('deactivates instead of deleting a redeemed reward', function () {
        $this->actingAs(User::factory()->create(['ouro' => 500]));
        $reward = Reward::factory()->create(['cost' => 100]);
        $this->post(route('recompensas.trocar', $reward));

        $this->delete(route('recompensas.destroy', $reward))->assertRedirect(route('recompensas.index'));

        expect($reward->refresh()->is_active)->toBeFalse();
        expect(RewardRedemption::count())->toBe(1);
    });
});
