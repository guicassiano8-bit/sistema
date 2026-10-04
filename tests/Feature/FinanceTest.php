<?php

use App\Enums\FinanceTab;
use App\Models\Account;
use App\Models\Asset;
use App\Models\FinanceCategory;
use App\Models\InvestmentTransaction;
use App\Models\Transaction;
use App\Models\Transfer;
use App\Models\User;
use App\Services\AccountBalanceService;

it('redirects guests to the login page', function () {
    $this->get(route('tesouro.index'))->assertRedirect(route('login.index'));
});

it('opens every tab with empty data', function (FinanceTab $aba) {
    $this->actingAs(User::factory()->create());

    $this->get(route('tesouro.index', ['aba' => $aba->value]))
        ->assertOk()
        ->assertViewHas('aba', $aba->value);
})->with(FinanceTab::cases());

it('falls back to the summary tab when the tab is unknown', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('tesouro.index', ['aba' => 'inexistente']))
        ->assertOk()
        ->assertViewHas('aba', 'resumo');
});

it('falls back to the current month when the statement month is invalid', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('tesouro.index', ['aba' => 'extrato', 'mes' => '2026-13']))
        ->assertViewHas('mes', fn ($mes) => $mes->isSameMonth(today()));

    $this->get(route('tesouro.index', ['aba' => 'extrato', 'mes' => '2026-03']))
        ->assertViewHas('mes', fn ($mes) => $mes->format('Y-m-d') === '2026-03-01');
});

it('offers only active categories to the launch modals, split by type', function () {
    $this->actingAs(User::factory()->create());

    $mercado = FinanceCategory::factory()->create(['name' => 'Mercado']);
    $salario = FinanceCategory::factory()->income()->create(['name' => 'Salário']);
    FinanceCategory::factory()->inactive()->create(['name' => 'Antiga']);

    $this->get(route('tesouro.index'))
        ->assertViewHas('categorias', [
            'gasto' => [$mercado->id => 'Mercado'],
            'ganho' => [$salario->id => 'Salário'],
        ]);
});

it('lists active assets for the yield modal', function () {
    $this->actingAs(User::factory()->create());

    Asset::factory()->create(['name' => 'CDB Ativo']);
    Asset::factory()->create(['name' => 'CDB Encerrado', 'is_active' => false]);

    $this->get(route('tesouro.index'))
        ->assertViewHas('investimentos', fn ($ativos) => $ativos->pluck('name')->all() === ['CDB Ativo']);
});

describe('account balance', function () {
    it('starts at the initial balance', function () {
        $conta = Account::factory()->create(['initial_balance' => '150.50']);

        expect((string) app(AccountBalanceService::class)->saldoDaConta($conta))->toBe('150.50');
    });

    it('adds paid income and subtracts paid expenses, ignoring pending ones', function () {
        $conta = Account::factory()->create(['initial_balance' => 100]);

        Transaction::factory()->income()->for($conta)->create(['amount' => '1000.10']);
        Transaction::factory()->for($conta)->create(['amount' => '250.30']);
        Transaction::factory()->for($conta)->pending()->create(['amount' => '999.00']);

        expect((string) app(AccountBalanceService::class)->saldoDaConta($conta))->toBe('849.80');
    });

    it('accounts for transfers and investment movements without float drift', function () {
        $conta = Account::factory()->create(['initial_balance' => 500]);
        $outra = Account::factory()->create();

        Transfer::create(['from_account_id' => $conta->id, 'to_account_id' => $outra->id, 'amount' => '0.10', 'date' => today()]);
        Transfer::create(['from_account_id' => $outra->id, 'to_account_id' => $conta->id, 'amount' => '0.20', 'date' => today()]);

        $ativo = Asset::factory()->create();
        InvestmentTransaction::factory()->for($ativo)->create(['account_id' => $conta->id, 'total' => '200.00']);
        InvestmentTransaction::factory()->withdrawal()->for($ativo)->create(['account_id' => $conta->id, 'total' => '50.00']);

        // 500 − 0,10 + 0,20 − 200 + 50
        expect((string) app(AccountBalanceService::class)->saldoDaConta($conta))->toBe('350.10');
    });
});
