<?php

use App\Enums\IncomeType;
use App\Enums\InvestmentTransactionType;
use App\Models\Account;
use App\Models\Asset;
use App\Models\AssetBalanceUpdate;
use App\Models\AssetType;
use App\Models\IncomeEntry;
use App\Models\InvestmentTransaction;
use App\Models\Quote;
use App\Models\User;
use App\Services\AccountBalanceService;
use App\Services\InvestmentService;
use Database\Seeders\AssetTypeSeeder;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->seed(AssetTypeSeeder::class);
});

function cdb(array $extra = []): array
{
    return $extra + ['type' => 'CDB', 'name' => 'CDB Banco X', 'amount' => '1.000,00', 'date' => '2026-09-01', 'indexer' => 'CDI', 'rate' => '110', 'maturity_date' => '2028-09-01'];
}

function fii(array $extra = []): array
{
    return $extra + ['type' => 'FII', 'name' => 'Maxi Renda', 'ticker' => 'mxrf11', 'amount' => '1.000,00', 'quantity' => '100', 'date' => '2026-09-01'];
}

function posicaoDe(Asset $asset)
{
    return app(InvestmentService::class)->posicao($asset->load(['assetType', 'latestQuote', 'latestBalanceUpdate', 'transactions', 'incomeEntries']));
}

it('requires login', function () {
    auth()->logout();

    $this->post(route('tesouro.investimentos.store'), cdb())->assertRedirect(route('login.index'));
});

describe('registering', function () {
    it('registers a CDB with its first deposit and balance', function () {
        $this->post(route('tesouro.investimentos.store'), cdb())
            ->assertRedirect(route('tesouro.index', ['aba' => 'ativos']))
            ->assertSessionHas('sys_toast.title', 'Investimento registrado');

        $asset = Asset::sole();
        $posicao = posicaoDe($asset);

        expect($asset->rate_label)->toBe('110% do CDI')
            ->and($asset->maturity_date->format('Y-m-d'))->toBe('2028-09-01')
            ->and(InvestmentTransaction::sole()->type)->toBe(InvestmentTransactionType::Deposit)
            ->and((string) $posicao->invested)->toBe('1000.00')
            ->and((string) $posicao->current)->toBe('1000.00');
    });

    it('registers a FII with the first purchase, using the cost until a quote exists', function () {
        $this->post(route('tesouro.investimentos.store'), fii());

        $asset = Asset::sole();
        $compra = InvestmentTransaction::sole();
        $posicao = posicaoDe($asset);

        expect($asset->ticker)->toBe('MXRF11')
            ->and($compra->type)->toBe(InvestmentTransactionType::Buy)
            ->and($compra->unit_price)->toBe('10.000000')
            ->and((string) $posicao->quantity)->toBe('100.000000')
            ->and((string) $posicao->current)->toBe('1000.00');
    });

    it('debits the chosen account', function () {
        $conta = Account::factory()->create(['initial_balance' => 5000]);

        $this->post(route('tesouro.investimentos.store'), cdb(['account_id' => $conta->id]));

        expect((string) app(AccountBalanceService::class)->saldoDaConta($conta))->toBe('4000.00');
    });

    it('validates in the "investimento" bag', function (array $dados, string $campo) {
        $this->post(route('tesouro.investimentos.store'), $dados)
            ->assertSessionHasErrors($campo, errorBag: 'investimento');

        expect(Asset::count())->toBe(0);
    })->with([
        'FII sem ticker' => [fn () => fii(['ticker' => '']), 'ticker'],
        'FII sem cotas' => [fn () => fii(['quantity' => '']), 'quantity'],
        'valor zero' => [fn () => cdb(['amount' => '0']), 'amount'],
        'tipo desconhecido' => [fn () => cdb(['type' => 'Ação']), 'type'],
        'taxa sem indexador' => [fn () => cdb(['indexer' => '']), 'indexer'],
    ]);

    it('does not accept a repeated ticker', function () {
        Asset::factory()->fii()->create(['ticker' => 'MXRF11']);

        $this->post(route('tesouro.investimentos.store'), fii())
            ->assertSessionHasErrors('ticker', errorBag: 'investimento');
    });
});

describe('fixed income', function () {
    it('moves the balance with deposits and withdrawals', function () {
        $asset = Asset::factory()->create();
        AssetBalanceUpdate::factory()->for($asset)->create(['reference_date' => '2026-09-01', 'gross_balance' => '1000.00']);
        InvestmentTransaction::factory()->for($asset)->create(['total' => '1000.00', 'date' => '2026-09-01']);

        $this->post(route('tesouro.investimentos.movimentos.store', $asset), ['type' => 'deposit', 'total' => '500,00', 'date' => '2026-09-10']);
        expect((string) posicaoDe($asset)->current)->toBe('1500.00');

        $this->post(route('tesouro.investimentos.movimentos.store', $asset), ['type' => 'withdrawal', 'total' => '300,00', 'date' => '2026-09-12']);
        $posicao = posicaoDe($asset);

        expect((string) $posicao->current)->toBe('1200.00')
            ->and((string) $posicao->invested)->toBe('1200.00');
    });

    it('does not allow withdrawing more than the balance', function () {
        $this->post(route('tesouro.investimentos.store'), cdb(['amount' => '100']));
        $asset = Asset::sole();

        $this->post(route('tesouro.investimentos.movimentos.store', $asset), ['type' => 'withdrawal', 'total' => '100,01', 'date' => '2026-09-10'])
            ->assertSessionHasErrors('total', errorBag: 'movimento');

        expect(InvestmentTransaction::count())->toBe(1);
    });

    it('rejects share operations on fixed income', function () {
        $asset = Asset::factory()->create();

        $this->post(route('tesouro.investimentos.movimentos.store', $asset), ['type' => 'buy', 'total' => '10', 'date' => '2026-09-10'])
            ->assertSessionHasErrors('type', errorBag: 'movimento');
    });

    it('records the balance informed by hand, with the net value', function () {
        $this->post(route('tesouro.investimentos.store'), cdb(['amount' => '1000']));
        $asset = Asset::sole();

        $this->post(route('tesouro.investimentos.valor.store', $asset), ['value' => '1.052,30', 'net_balance' => '1.040,10', 'date' => '2026-10-01'])
            ->assertSessionHasNoErrors();

        $posicao = posicaoDe($asset);
        expect((string) $posicao->current)->toBe('1052.30')
            ->and((string) $posicao->result())->toBe('52.30')
            ->and($asset->latestBalanceUpdate->net_balance)->toBe('1040.10');
    });
});

describe('shares (FII)', function () {
    function fiiComCompra(): Asset
    {
        test()->post(route('tesouro.investimentos.store'), fii(['amount' => '100', 'quantity' => '10']));

        return Asset::sole();
    }

    it('values the position at quantity times the latest quote', function () {
        $asset = fiiComCompra();

        $this->post(route('tesouro.investimentos.valor.store', $asset), ['value' => '10,55', 'date' => '2026-09-20']);
        $this->post(route('tesouro.investimentos.valor.store', $asset), ['value' => '10,60', 'date' => '2026-09-20']);

        $posicao = posicaoDe($asset);
        expect(Quote::count())->toBe(1)
            ->and((string) $posicao->current)->toBe('106.00')
            ->and((string) $posicao->result())->toBe('6.00');
    });

    it('averages the cost across purchases, fees included', function () {
        $asset = fiiComCompra();

        // 10 cotas a R$ 10,00 + 10 cotas a R$ 12,00 e R$ 2,00 de taxa: custo 222 para 20 cotas
        $this->post(route('tesouro.investimentos.movimentos.store', $asset), [
            'type' => 'buy', 'quantity' => '10', 'unit_price' => '12,00', 'fees' => '2,00', 'date' => '2026-09-05',
        ]);

        $posicao = posicaoDe($asset);
        expect((string) $posicao->quantity)->toBe('20.000000')
            ->and((string) $posicao->invested)->toBe('222.00');
    });

    it('freezes the average cost and the realized profit on a sale', function () {
        $asset = fiiComCompra();

        $this->post(route('tesouro.investimentos.movimentos.store', $asset), [
            'type' => 'sell', 'quantity' => '4', 'unit_price' => '12,00', 'fees' => '1,00', 'date' => '2026-09-10',
        ])->assertSessionHasNoErrors();

        $venda = InvestmentTransaction::query()->where('type', InvestmentTransactionType::Sell)->sole();
        $posicao = posicaoDe($asset);

        // custo médio 10,00 · recebeu 48,00 − custo 40,00 − taxa 1,00
        expect($venda->average_cost)->toBe('10.000000')
            ->and($venda->realized_profit)->toBe('7.00')
            ->and($venda->total)->toBe('48.00')
            ->and((string) $posicao->quantity)->toBe('6.000000')
            ->and((string) $posicao->invested)->toBe('60.00');
    });

    it('does not allow selling more shares than owned', function () {
        $asset = fiiComCompra();

        $this->post(route('tesouro.investimentos.movimentos.store', $asset), [
            'type' => 'sell', 'quantity' => '11', 'unit_price' => '12', 'date' => '2026-09-10',
        ])->assertSessionHasErrors('quantity', errorBag: 'movimento');

        expect(InvestmentTransaction::count())->toBe(1);
    });

    it('credits the account on a sale', function () {
        $conta = Account::factory()->create(['initial_balance' => 0]);
        $asset = fiiComCompra();

        $this->post(route('tesouro.investimentos.movimentos.store', $asset), [
            'type' => 'sell', 'quantity' => '5', 'unit_price' => '10', 'date' => '2026-09-10', 'account_id' => $conta->id,
        ]);

        expect((string) app(AccountBalanceService::class)->saldoDaConta($conta))->toBe('50.00');
    });
});

describe('income', function () {
    it('records a dividend for a FII and interest for a CDB, in the payment month', function () {
        $fii = Asset::factory()->fii()->create();
        $cdb = Asset::factory()->create();

        $this->post(route('tesouro.rendimentos.store'), ['asset_id' => $fii->id, 'amount' => '12,34', 'payment_date' => '2026-09-14'])
            ->assertSessionHas('sys_toast.title', 'Rendimento registrado');
        $this->post(route('tesouro.rendimentos.store'), ['asset_id' => $cdb->id, 'amount' => '5', 'payment_date' => '2026-10-02']);

        $dividendo = IncomeEntry::query()->where('asset_id', $fii->id)->sole();
        $juros = IncomeEntry::query()->where('asset_id', $cdb->id)->sole();

        expect($dividendo->type)->toBe(IncomeType::Dividend)
            ->and($dividendo->amount)->toBe('12.34')
            ->and($dividendo->reference_month->format('Y-m-d'))->toBe('2026-09-01')
            ->and($juros->type)->toBe(IncomeType::Interest)
            ->and($juros->reference_month->format('Y-m-d'))->toBe('2026-10-01');
    });

    it('validates in the "rendimento" bag and ignores inactive assets', function () {
        $inativo = Asset::factory()->create(['is_active' => false]);

        $this->post(route('tesouro.rendimentos.store'), ['asset_id' => $inativo->id, 'amount' => '0', 'payment_date' => '2026-09-14'])
            ->assertSessionHasErrors(['asset_id', 'amount'], errorBag: 'rendimento');
    });

    it('deletes an income entry', function () {
        $rendimento = IncomeEntry::factory()->create();

        $this->delete(route('tesouro.rendimentos.destroy', $rendimento))->assertRedirect();

        expect(IncomeEntry::count())->toBe(0);
    });
});

describe('editing and deleting', function () {
    it('updates the asset data', function () {
        $asset = Asset::factory()->create(['name' => 'Antigo']);

        $this->put(route('tesouro.investimentos.update', $asset), ['name' => 'Novo', 'indexer' => 'IPCA', 'rate' => '6,5', 'is_active' => '1'])
            ->assertRedirect(route('tesouro.index', ['aba' => 'ativos']));

        expect($asset->fresh())
            ->name->toBe('Novo')
            ->rate_label->toBe('IPCA + 6,5%');
    });

    it('lets a FII keep its own ticker and rejects another one in use', function () {
        $asset = Asset::factory()->fii()->create(['ticker' => 'MXRF11']);
        Asset::factory()->fii()->create(['ticker' => 'HGLG11']);

        $this->put(route('tesouro.investimentos.update', $asset), ['name' => 'Maxi', 'ticker' => 'mxrf11', 'is_active' => '1'])
            ->assertSessionHasNoErrors();
        $this->put(route('tesouro.investimentos.update', $asset), ['name' => 'Maxi', 'ticker' => 'HGLG11', 'is_active' => '1'])
            ->assertSessionHasErrors('ticker', errorBag: 'investimento');
    });

    it('opens the management page for both kinds', function (Closure $criar) {
        $this->get(route('tesouro.investimentos.edit', $criar()))->assertOk();
    })->with([
        'CDB' => [function () {
            test()->post(route('tesouro.investimentos.store'), cdb());

            return Asset::sole();
        }],
        'FII' => [function () {
            test()->post(route('tesouro.investimentos.store'), fii());

            return Asset::sole();
        }],
    ]);

    it('deletes a registration that only has entries, along with its movements', function () {
        $this->post(route('tesouro.investimentos.store'), cdb());
        $asset = Asset::sole();

        $this->delete(route('tesouro.investimentos.destroy', $asset))->assertSessionHas('sys_toast.title', 'Investimento excluído');

        expect(Asset::count())->toBe(0)
            ->and(InvestmentTransaction::count())->toBe(0)
            ->and(AssetBalanceUpdate::count())->toBe(0);
    });

    it('only closes an asset that has income or withdrawals', function (Closure $historico) {
        $asset = Asset::factory()->create();
        $historico($asset);

        $this->delete(route('tesouro.investimentos.destroy', $asset))->assertSessionHas('sys_toast.title', 'Investimento encerrado');

        expect($asset->fresh()->is_active)->toBeFalse();
    })->with([
        'rendimento' => [fn (Asset $a) => IncomeEntry::factory()->for($a)->create()],
        'resgate' => [fn (Asset $a) => InvestmentTransaction::factory()->withdrawal()->for($a)->create()],
    ]);
});

describe('assets tab', function () {
    it('shows the portfolio with fixed income and shares', function () {
        $this->post(route('tesouro.investimentos.store'), cdb());
        $this->post(route('tesouro.investimentos.store'), fii());
        $fii = Asset::query()->where('ticker', 'MXRF11')->sole();
        IncomeEntry::factory()->for($fii)->create(['amount' => '25.00']);

        $this->get(route('tesouro.index', ['aba' => 'ativos']))
            ->assertOk()
            ->assertSee('CDB Banco X')
            ->assertSee('110% do CDI')
            ->assertSee('MXRF11')
            ->assertSee('100 cotas')
            ->assertSee('0,25/cota')
            ->assertViewHas('posicoes', fn ($p) => $p->count() === 2);
    });

    it('lists active assets in the income modal and hides closed ones', function () {
        Asset::factory()->create(['name' => 'CDB Ativo']);
        Asset::factory()->create(['name' => 'CDB Encerrado', 'is_active' => false]);

        $this->get(route('tesouro.index', ['aba' => 'ativos']))
            ->assertSee('CDB Ativo')
            ->assertDontSee('CDB Encerrado');
    });
});

describe('result with income', function () {
    it('adds received income to what a FII earned', function () {
        $this->post(route('tesouro.investimentos.store'), fii(['amount' => '100', 'quantity' => '10']));
        $asset = Asset::sole();
        $this->post(route('tesouro.investimentos.valor.store', $asset), ['value' => '10,60', 'date' => '2026-09-20']);

        expect((string) posicaoDe($asset)->result())->toBe('6.00');

        $this->post(route('tesouro.rendimentos.store'), ['asset_id' => $asset->id, 'amount' => '5,00', 'payment_date' => '2026-09-25']);
        $posicao = posicaoDe($asset);

        expect((string) $posicao->income)->toBe('5.00')
            ->and((string) $posicao->result())->toBe('11.00')
            ->and($posicao->resultPercent())->toBe(11.0)
            ->and((string) $posicao->current)->toBe('106.00');
    });

    it('adds received income to what a CDB earned and drops it when the entry is deleted', function () {
        $this->post(route('tesouro.investimentos.store'), cdb(['amount' => '1000']));
        $asset = Asset::sole();
        $this->post(route('tesouro.investimentos.valor.store', $asset), ['value' => '1.052,30', 'date' => '2026-10-01']);
        $this->post(route('tesouro.rendimentos.store'), ['asset_id' => $asset->id, 'amount' => '8', 'payment_date' => '2026-10-02']);

        expect((string) posicaoDe($asset)->result())->toBe('60.30');

        $this->delete(route('tesouro.rendimentos.destroy', IncomeEntry::sole()));

        expect((string) posicaoDe($asset)->result())->toBe('52.30');
    });

    it('shows the income inside the card and the portfolio total', function () {
        $this->post(route('tesouro.investimentos.store'), cdb(['amount' => '1000']));
        $asset = Asset::sole();
        $this->post(route('tesouro.rendimentos.store'), ['asset_id' => $asset->id, 'amount' => '8', 'payment_date' => '2026-10-02']);

        $this->get(route('tesouro.index', ['aba' => 'ativos']))
            ->assertSee('c/ rendimentos')
            ->assertSee('+R$&nbsp;8,00', false);
    });
});

it('keeps the Quote type for fixed income out of the share flow', function () {
    expect(AssetType::query()->where('name', 'CDB')->value('is_market_traded'))->toBeFalsy();
});
