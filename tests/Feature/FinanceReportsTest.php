<?php

use App\Models\Account;
use App\Models\Asset;
use App\Models\AssetBalanceUpdate;
use App\Models\FinanceCategory;
use App\Models\InvestmentTransaction;
use App\Models\PortfolioSnapshot;
use App\Models\Quote;
use App\Models\Transaction;
use App\Models\Transfer;
use App\Models\User;
use App\Services\AccountBalanceService;
use App\Services\FinanceReportService;
use Database\Seeders\AssetTypeSeeder;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->seed(AssetTypeSeeder::class);
    $this->travelTo('2026-10-15 12:00:00');
});

function relatorios(): array
{
    return app(FinanceReportService::class)->relatorios(today());
}

function cdbCom(string $aplicado, string $data, array $saldos = []): Asset
{
    $asset = Asset::factory()->create();
    InvestmentTransaction::factory()->for($asset)->create(['total' => $aplicado, 'date' => $data]);

    foreach ($saldos as $dia => $saldo) {
        AssetBalanceUpdate::factory()->for($asset)->create(['reference_date' => $dia, 'gross_balance' => $saldo]);
    }

    return $asset;
}

it('opens the reports tab with no data and shows the empty states', function () {
    $this->get(route('tesouro.index', ['aba' => 'relatorios']))
        ->assertOk()
        ->assertViewHas('serie12m', [])
        ->assertViewHas('porCategoria', [])
        ->assertViewHas('fluxo6m', ['labels' => [], 'ganhos' => [], 'gastos' => []]);
});

describe('net worth series', function () {
    it('values each month-end with the balance known on that day', function () {
        Account::factory()->create(['initial_balance' => '1000.00']);
        Transaction::factory()->income()->create([
            'account_id' => Account::sole()->id,
            'finance_category_id' => FinanceCategory::factory()->income()->create()->id,
            'amount' => '500.00', 'date' => '2026-08-10',
        ]);
        cdbCom('1000.00', '2026-07-05', ['2026-08-31' => '1100.00', '2026-10-10' => '1200.00']);

        $serie = collect(relatorios()['serie12m'])->pluck('value', 'label');

        expect($serie)->toHaveCount(12)
            ->and($serie->keys()->first())->toBe('nov/25')
            ->and($serie->keys()->last())->toBe('out/26')
            ->and($serie['jun/26'])->toBe(1000.0)   // só a conta
            ->and($serie['jul/26'])->toBe(2000.0)   // sem saldo informado: vale o aplicado
            ->and($serie['ago/26'])->toBe(2600.0)   // 1000 + 500 + 1100
            ->and($serie['set/26'])->toBe(2600.0)
            ->and($serie['out/26'])->toBe(2700.0);  // 1500 + 1200 (hoje)
    });

    it('cuts the months before the first data', function () {
        Account::factory()->create(['initial_balance' => '0']);
        Transaction::factory()->income()->create([
            'account_id' => Account::sole()->id,
            'finance_category_id' => FinanceCategory::factory()->income()->create()->id,
            'amount' => '100.00', 'date' => '2026-08-10',
        ]);

        expect(collect(relatorios()['serie12m'])->pluck('label')->all())->toBe(['ago/26', 'set/26', 'out/26']);
    });

    it('uses the stored snapshot for closed months and recomputes the ones without', function () {
        Account::factory()->create(['initial_balance' => '100.00']);
        $asset = cdbCom('1000.00', '2026-07-05', ['2026-08-31' => '1100.00']);
        PortfolioSnapshot::create(['asset_id' => $asset->id, 'reference_month' => '2026-08-01', 'invested_amount' => '1000.00', 'market_value' => '9999.00']);

        $serie = collect(relatorios()['serie12m'])->pluck('value', 'label');

        expect($serie['ago/26'])->toBe(10099.0)  // fotografia: 100 + 9999
            ->and($serie['set/26'])->toBe(1200.0); // sem fotografia: 100 + saldo 1100
    });
});

describe('cash flow', function () {
    it('totals paid income and expenses for the last six months, oldest first', function () {
        $conta = Account::factory()->create();
        $gasto = FinanceCategory::factory()->create();
        $ganho = FinanceCategory::factory()->income()->create();
        Transaction::factory()->income()->for($conta)->create(['finance_category_id' => $ganho->id, 'amount' => '3000.00', 'date' => '2026-10-31']);
        Transaction::factory()->for($conta)->create(['finance_category_id' => $gasto->id, 'amount' => '800.50', 'date' => '2026-10-02']);
        Transaction::factory()->for($conta)->create(['finance_category_id' => $gasto->id, 'amount' => '120.00', 'date' => '2026-05-20']);
        Transaction::factory()->for($conta)->pending()->create(['finance_category_id' => $gasto->id, 'amount' => '999.00', 'date' => '2026-10-10']);
        Transaction::factory()->for($conta)->create(['finance_category_id' => $gasto->id, 'amount' => '555.00', 'date' => '2026-04-30']); // fora da janela

        expect(relatorios()['fluxo6m'])->toBe([
            'labels' => ['mai', 'jun', 'jul', 'ago', 'set', 'out'],
            'ganhos' => [0.0, 0.0, 0.0, 0.0, 0.0, 3000.0],
            'gastos' => [120.0, 0.0, 0.0, 0.0, 0.0, 800.5],
        ]);
    });
});

describe('spending by category', function () {
    it('ranks the current month paid expenses from the biggest to the smallest', function () {
        $conta = Account::factory()->create();
        $mercado = FinanceCategory::factory()->create(['name' => 'Mercado']);
        $lazer = FinanceCategory::factory()->create(['name' => 'Lazer']);
        $salario = FinanceCategory::factory()->income()->create();
        Transaction::factory()->for($conta)->create(['finance_category_id' => $lazer->id, 'amount' => '50.00', 'date' => '2026-10-03']);
        Transaction::factory()->for($conta)->create(['finance_category_id' => $mercado->id, 'amount' => '300.00', 'date' => '2026-10-01']);
        Transaction::factory()->for($conta)->create(['finance_category_id' => $mercado->id, 'amount' => '100.25', 'date' => '2026-10-31']);
        Transaction::factory()->for($conta)->pending()->create(['finance_category_id' => $lazer->id, 'amount' => '900.00', 'date' => '2026-10-04']);
        Transaction::factory()->for($conta)->create(['finance_category_id' => $mercado->id, 'amount' => '777.00', 'date' => '2026-09-30']);
        Transaction::factory()->income()->for($conta)->create(['finance_category_id' => $salario->id, 'amount' => '5000.00', 'date' => '2026-10-05']);

        expect(relatorios()['porCategoria'])->toBe([
            ['label' => 'Mercado', 'value' => 400.25],
            ['label' => 'Lazer', 'value' => 50.0],
        ]);
    });

    it('groups everything past the seventh category as "Demais categorias"', function () {
        $conta = Account::factory()->create();

        foreach (range(1, 9) as $n) {
            Transaction::factory()->for($conta)->create([
                'finance_category_id' => FinanceCategory::factory()->create(['name' => "Cat $n"])->id,
                'amount' => (string) (100 - $n), 'date' => '2026-10-02',
            ]);
        }

        $itens = relatorios()['porCategoria'];

        expect($itens)->toHaveCount(8)
            ->and($itens[0])->toBe(['label' => 'Cat 1', 'value' => 99.0])
            ->and($itens[7])->toBe(['label' => 'Demais categorias', 'value' => 183.0]); // 92 + 91
    });
});

describe('finance:snapshot', function () {
    it('photographs the current month with today\'s values and is idempotent', function () {
        $cdb = cdbCom('1000.00', '2026-07-05', ['2026-10-10' => '1150.00']);
        $fii = Asset::factory()->fii()->create();
        InvestmentTransaction::factory()->for($fii)->create(['type' => 'buy', 'quantity' => 10, 'unit_price' => 10, 'total' => '100.00', 'date' => '2026-09-01']);
        Quote::create(['asset_id' => $fii->id, 'date' => '2026-10-12', 'price' => '10.60']);
        cdbCom('50.00', '2026-07-05')->update(['is_active' => false]);

        $this->artisan('finance:snapshot')->expectsOutputToContain('2 ativo(s) fotografado(s) em 10/2026.')->assertSuccessful();
        $this->artisan('finance:snapshot')->assertSuccessful();

        expect(PortfolioSnapshot::count())->toBe(2)
            ->and(PortfolioSnapshot::query()->where('asset_id', $cdb->id)->sole())
            ->reference_month->format('Y-m-d')->toBe('2026-10-01')
            ->invested_amount->toBe('1000.00')
            ->market_value->toBe('1150.00')
            ->and(PortfolioSnapshot::query()->where('asset_id', $fii->id)->sole())
            ->invested_amount->toBe('100.00')
            ->market_value->toBe('106.00');
    });

    it('photographs a past month with the values of its last day', function () {
        cdbCom('1000.00', '2026-07-05', ['2026-09-15' => '1100.00', '2026-10-10' => '1150.00']);

        $this->artisan('finance:snapshot', ['--mes' => '2026-09'])->assertSuccessful();

        expect(PortfolioSnapshot::sole())
            ->reference_month->format('Y-m-d')->toBe('2026-09-01')
            ->market_value->toBe('1100.00');
    });

    it('rejects a malformed month', function () {
        $this->artisan('finance:snapshot', ['--mes' => '09/2026'])->assertFailed();

        expect(PortfolioSnapshot::count())->toBe(0);
    });
});

it('totals the active accounts in one pass, matching the per-account balances', function () {
    $a = Account::factory()->create(['initial_balance' => '100.00']);
    $b = Account::factory()->create(['initial_balance' => '50.00']);
    Account::factory()->inactive()->create(['initial_balance' => '999.00']);
    $categoria = FinanceCategory::factory()->create();
    Transaction::factory()->for($a)->create(['finance_category_id' => $categoria->id, 'amount' => '30.10', 'date' => '2026-10-01']);
    Transfer::create(['from_account_id' => $a->id, 'to_account_id' => $b->id, 'amount' => '20.00', 'date' => '2026-10-02']);
    InvestmentTransaction::factory()->for(Asset::factory()->create())->create(['account_id' => $b->id, 'total' => '10.00']);

    $saldos = app(AccountBalanceService::class);

    expect((string) $saldos->saldoTotal())->toBe('109.90')
        ->and((string) $saldos->saldoTotal())->toBe((string) $saldos->saldoDaConta($a)->plus($saldos->saldoDaConta($b)))
        ->and((string) $saldos->saldoTotal(now()->subMonth()))->toBe('150.00');
});

it('renders the reports tab with data', function () {
    $conta = Account::factory()->create(['initial_balance' => '1000.00']);
    Transaction::factory()->for($conta)->create(['finance_category_id' => FinanceCategory::factory()->create(['name' => 'Mercado'])->id, 'amount' => '300.00', 'date' => '2026-10-01']);

    $this->get(route('tesouro.index', ['aba' => 'relatorios']))
        ->assertOk()
        ->assertSee('Mercado')
        ->assertSee('out/26');
});
