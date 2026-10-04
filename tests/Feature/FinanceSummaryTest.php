<?php

use App\Models\Account;
use App\Models\Asset;
use App\Models\AssetBalanceUpdate;
use App\Models\FinanceCategory;
use App\Models\IncomeEntry;
use App\Models\InvestmentTransaction;
use App\Models\Quote;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AccountBalanceService;
use App\Services\FinanceReportService;
use Database\Seeders\AssetTypeSeeder;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->seed(AssetTypeSeeder::class);
    $this->travelTo('2026-10-15 12:00:00');
    $this->conta = Account::factory()->create(['initial_balance' => '1000.00']);
});

function resumo(): array
{
    return app(FinanceReportService::class)->resumo(today());
}

it('opens the summary tab with no data at all', function () {
    Account::query()->delete();

    $this->get(route('tesouro.index'))
        ->assertOk()
        ->assertSee('Sem mês anterior para comparar.')
        ->assertViewHas('resumo', fn ($r) => $r['patrimonio'] === '0.00' && $r['delta_pct'] === null && $r['pct_gasto'] === 0);
});

describe('net worth', function () {
    it('adds the account balances to the current value of fixed income and shares', function () {
        $cdb = Asset::factory()->create();
        InvestmentTransaction::factory()->for($cdb)->create(['total' => '1000.00', 'date' => '2026-08-10']);
        AssetBalanceUpdate::factory()->for($cdb)->create(['reference_date' => '2026-10-10', 'gross_balance' => '1150.00']);

        $fii = Asset::factory()->fii()->create();
        InvestmentTransaction::factory()->for($fii)->create([
            'type' => 'buy', 'quantity' => 10, 'unit_price' => 10, 'total' => '100.00', 'date' => '2026-09-01',
        ]);
        Quote::create(['asset_id' => $fii->id, 'date' => '2026-10-12', 'price' => '10.60']);

        $r = resumo();

        expect($r['contas'])->toBe('1000.00')
            ->and($r['investido'])->toBe('1256.00')
            ->and($r['patrimonio'])->toBe('2256.00');
    });

    it('leaves out inactive accounts and closed assets, and does not count income as net worth', function () {
        Account::factory()->inactive()->create(['initial_balance' => '500.00']);
        $encerrado = Asset::factory()->create(['is_active' => false]);
        InvestmentTransaction::factory()->for($encerrado)->create(['total' => '300.00']);
        IncomeEntry::factory()->for(Asset::factory()->create())->create(['amount' => '50.00']);

        expect(resumo()['patrimonio'])->toBe('1000.00');
    });

    it('compares with the end of the previous month, using what existed on that day', function () {
        $categoria = FinanceCategory::factory()->create();
        $receita = FinanceCategory::factory()->income()->create();
        Transaction::factory()->income()->for($this->conta)->create(['finance_category_id' => $receita->id, 'amount' => '500.00', 'date' => '2026-09-20']);
        Transaction::factory()->for($this->conta)->create(['finance_category_id' => $categoria->id, 'amount' => '200.00', 'date' => '2026-10-05']);

        $cdb = Asset::factory()->create();
        InvestmentTransaction::factory()->for($cdb)->create(['total' => '1000.00', 'date' => '2026-08-10']);
        AssetBalanceUpdate::factory()->for($cdb)->create(['reference_date' => '2026-09-15', 'gross_balance' => '1100.00']);
        AssetBalanceUpdate::factory()->for($cdb)->create(['reference_date' => '2026-10-10', 'gross_balance' => '1150.00']);

        $r = resumo();

        // fim de setembro: 1000 + 500 + 1100 = 2600 · hoje: 1000 + 500 − 200 + 1150 = 2450
        expect($r['patrimonio'])->toBe('2450.00')
            ->and($r['delta_pct'])->toBe(-5.77);
    });

    it('has no variation when there was nothing to compare against', function () {
        Account::factory()->create(['initial_balance' => '0']);
        $this->conta->delete();
        Transaction::factory()->income()->create([
            'account_id' => Account::query()->first()->id,
            'finance_category_id' => FinanceCategory::factory()->income()->create()->id,
            'amount' => '100.00', 'date' => '2026-10-02',
        ]);

        expect(resumo())->toMatchArray(['patrimonio' => '100.00', 'delta_pct' => null]);
    });
});

describe('month flow', function () {
    it('totals only paid transactions of the current month, including the last day of the month', function () {
        $gasto = FinanceCategory::factory()->create();
        $ganho = FinanceCategory::factory()->income()->create();
        Transaction::factory()->income()->for($this->conta)->create(['finance_category_id' => $ganho->id, 'amount' => '4000.00', 'date' => '2026-10-31']);
        Transaction::factory()->for($this->conta)->create(['finance_category_id' => $gasto->id, 'amount' => '1000.00', 'date' => '2026-10-01']);
        Transaction::factory()->for($this->conta)->pending()->create(['finance_category_id' => $gasto->id, 'amount' => '999.00', 'date' => '2026-10-10']);
        Transaction::factory()->for($this->conta)->create(['finance_category_id' => $gasto->id, 'amount' => '777.00', 'date' => '2026-09-30']);

        expect(resumo())->toMatchArray([
            'ganhos_mes' => '4000.00',
            'gastos_mes' => '1000.00',
            'saldo_mes' => '3000.00',
            'pct_gasto' => 25,
        ]);
    });

    it('reports 100% spent when there is spending but no income, and 0% when there is neither', function () {
        expect(resumo()['pct_gasto'])->toBe(0);

        Transaction::factory()->for($this->conta)->create(['finance_category_id' => FinanceCategory::factory()->create()->id, 'amount' => '10.00', 'date' => '2026-10-02']);

        expect(resumo()['pct_gasto'])->toBe(100);
    });

    it('sums passive income paid in the month', function () {
        $ativo = Asset::factory()->fii()->create();
        IncomeEntry::factory()->for($ativo)->create(['amount' => '12.30', 'payment_date' => '2026-10-31']);
        IncomeEntry::factory()->for($ativo)->create(['amount' => '7.70', 'payment_date' => '2026-10-02']);
        IncomeEntry::factory()->for($ativo)->create(['amount' => '99.00', 'payment_date' => '2026-09-30']);

        expect(resumo()['passivo_mes'])->toBe('20.00');
    });

    it('lists the five most recent transactions, newest first', function () {
        $categoria = FinanceCategory::factory()->create();
        foreach (range(1, 7) as $dia) {
            Transaction::factory()->for($this->conta)->create([
                'finance_category_id' => $categoria->id, 'description' => "Gasto $dia", 'date' => "2026-10-0$dia",
            ]);
        }

        $this->get(route('tesouro.index'))
            ->assertViewHas('ultimos', fn ($lista) => $lista->pluck('description')->all() === ['Gasto 7', 'Gasto 6', 'Gasto 5', 'Gasto 4', 'Gasto 3'])
            ->assertSee('Gasto 7')
            ->assertDontSee('Gasto 1');
    });
});

it('computes the balance of an account as of a past day', function () {
    $categoria = FinanceCategory::factory()->create();
    Transaction::factory()->for($this->conta)->create(['finance_category_id' => $categoria->id, 'amount' => '100.00', 'date' => '2026-09-30']);
    Transaction::factory()->for($this->conta)->create(['finance_category_id' => $categoria->id, 'amount' => '50.00', 'date' => '2026-10-01']);

    $saldos = app(AccountBalanceService::class);

    expect((string) $saldos->saldoDaConta($this->conta, now()->subMonth()->endOfMonth()))->toBe('900.00')
        ->and((string) $saldos->saldoDaConta($this->conta))->toBe('850.00');
});
