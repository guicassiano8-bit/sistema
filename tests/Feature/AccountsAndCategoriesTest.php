<?php

use App\Enums\AccountType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Asset;
use App\Models\FinanceCategory;
use App\Models\InvestmentTransaction;
use App\Models\Transaction;
use App\Models\Transfer;
use App\Models\User;
use App\Support\Money;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('requires login for the registers', function () {
    auth()->logout();

    $this->get(route('tesouro.contas.index'))->assertRedirect(route('login.index'));
    $this->get(route('tesouro.categorias.index'))->assertRedirect(route('login.index'));
});

describe('accounts', function () {
    it('lists accounts with their calculated balance and the active total', function () {
        $nubank = Account::factory()->create(['name' => 'Nubank', 'initial_balance' => '100.00']);
        Transaction::factory()->income()->for($nubank)->create(['amount' => '50.50']);
        Account::factory()->create(['name' => 'Carteira', 'initial_balance' => '10.00']);
        Account::factory()->inactive()->create(['name' => 'Antiga', 'initial_balance' => '999.00']);

        $this->get(route('tesouro.contas.index'))
            ->assertOk()
            ->assertSeeInOrder(['Carteira', 'Nubank', 'Antiga'])
            ->assertViewHas('total', fn ($total) => (string) $total === '160.50');
    });

    it('creates an account, accepting a Brazilian or negative initial balance', function (string $digitado, string $esperado) {
        $this->post(route('tesouro.contas.store'), ['name' => 'Inter', 'type' => 'checking', 'initial_balance' => $digitado])
            ->assertRedirect(route('tesouro.contas.index'))
            ->assertSessionHas('sys_toast.title', 'Conta cadastrada');

        expect(Account::sole())
            ->initial_balance->toBe($esperado)
            ->is_active->toBeTrue();
    })->with([
        'formato brasileiro' => ['1.500,75', '1500.75'],
        'negativo' => ['-200,00', '-200.00'],
    ]);

    it('defaults the initial balance to zero', function () {
        $this->post(route('tesouro.contas.store'), ['name' => 'Inter', 'type' => 'wallet']);

        expect(Account::sole()->initial_balance)->toBe('0.00');
    });

    it('validates in the "conta" bag and does not offer credit cards yet', function (array $dados, string $campo) {
        $this->post(route('tesouro.contas.store'), $dados + ['name' => 'X', 'type' => 'checking'])
            ->assertSessionHasErrors($campo, errorBag: 'conta');
    })->with([
        'sem nome' => [['name' => ''], 'name'],
        'cartão de crédito' => [['type' => AccountType::CreditCard->value], 'type'],
        'saldo inválido' => [['initial_balance' => 'muito'], 'initial_balance'],
    ]);

    it('updates an account and the checkbox controls whether it is active', function () {
        $conta = Account::factory()->create();

        $this->put(route('tesouro.contas.update', $conta), ['name' => 'Novo nome', 'type' => 'brokerage', 'initial_balance' => '5,00'])
            ->assertRedirect(route('tesouro.contas.index'));

        expect($conta->fresh())
            ->name->toBe('Novo nome')
            ->type->toBe(AccountType::Brokerage)
            ->is_active->toBeFalse();

        $this->put(route('tesouro.contas.update', $conta), ['name' => 'Novo nome', 'type' => 'brokerage', 'is_active' => '1']);

        expect($conta->fresh()->is_active)->toBeTrue();
    });

    it('opens the edit page with the current balance', function () {
        $conta = Account::factory()->create(['initial_balance' => '1234.50']);

        $this->get(route('tesouro.contas.edit', $conta))->assertOk()->assertSee('1.234,50');
    });

    it('deletes an account without history', function () {
        $conta = Account::factory()->create();

        $this->delete(route('tesouro.contas.destroy', $conta))->assertSessionHas('sys_toast.title', 'Conta excluída');

        expect(Account::count())->toBe(0);
    });

    it('only deactivates an account that has history', function (Closure $historico) {
        $conta = Account::factory()->create();
        $historico($conta);

        $this->delete(route('tesouro.contas.destroy', $conta))->assertSessionHas('sys_toast.title', 'Conta desativada');

        expect($conta->fresh()->is_active)->toBeFalse();
    })->with([
        'lançamento' => [fn (Account $conta) => Transaction::factory()->for($conta)->create()],
        'transferência recebida' => [fn (Account $conta) => Transfer::create(['from_account_id' => Account::factory()->create()->id, 'to_account_id' => $conta->id, 'amount' => 10, 'date' => today()])],
        'aporte em investimento' => [fn (Account $conta) => InvestmentTransaction::factory()->for(Asset::factory()->create())->create(['account_id' => $conta->id])],
    ]);

    it('stops offering a deactivated account as the default for new transactions', function () {
        Account::factory()->inactive()->create(['name' => 'Antiga']);
        $ativa = Account::factory()->create(['name' => 'Ativa']);
        $categoria = FinanceCategory::factory()->create();

        $this->post(route('tesouro.lancamentos.store'), [
            'type' => 'expense', 'amount' => '10', 'date' => '2026-09-10', 'finance_category_id' => $categoria->id,
        ]);

        expect(Transaction::sole()->account_id)->toBe($ativa->id);
    });
});

describe('categories', function () {
    it('lists expense and income categories separately with their usage count', function () {
        $mercado = FinanceCategory::factory()->create(['name' => 'Mercado']);
        FinanceCategory::factory()->income()->create(['name' => 'Salário']);
        Transaction::factory()->count(2)->create(['finance_category_id' => $mercado->id]);

        $this->get(route('tesouro.categorias.index'))
            ->assertOk()
            ->assertSee('2 lançamentos')
            ->assertViewHas('gastos', fn ($lista) => $lista->pluck('name')->all() === ['Mercado'])
            ->assertViewHas('ganhos', fn ($lista) => $lista->pluck('name')->all() === ['Salário']);
    });

    it('creates a category', function () {
        $this->post(route('tesouro.categorias.store'), ['type' => 'expense', 'name' => 'Pets', 'color' => '#10B981'])
            ->assertRedirect(route('tesouro.categorias.index'));

        expect(FinanceCategory::sole())
            ->name->toBe('Pets')
            ->type->toBe(TransactionType::Expense)
            ->color->toBe('#10B981')
            ->is_active->toBeTrue();
    });

    it('allows the same name once per type but not twice in the same type', function () {
        FinanceCategory::factory()->create(['name' => 'Outros']);

        $this->post(route('tesouro.categorias.store'), ['type' => 'income', 'name' => 'Outros'])->assertSessionHasNoErrors();
        $this->post(route('tesouro.categorias.store'), ['type' => 'expense', 'name' => 'Outros'])
            ->assertSessionHasErrors('name', errorBag: 'categoria');

        expect(FinanceCategory::count())->toBe(2);
    });

    it('rejects an invalid color', function () {
        $this->post(route('tesouro.categorias.store'), ['type' => 'expense', 'name' => 'Pets', 'color' => 'verde'])
            ->assertSessionHasErrors('color', errorBag: 'categoria');
    });

    it('updates a category without changing its type and keeps its own name valid', function () {
        $categoria = FinanceCategory::factory()->create(['name' => 'Mercado']);

        $this->put(route('tesouro.categorias.update', $categoria), ['name' => 'Mercado', 'type' => 'income', 'color' => '#111111', 'is_active' => '1'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('tesouro.categorias.index'));

        expect($categoria->fresh())
            ->type->toBe(TransactionType::Expense)
            ->color->toBe('#111111');
    });

    it('hides a deactivated category from the launch modals', function () {
        $categoria = FinanceCategory::factory()->create(['name' => 'Mercado']);

        $this->put(route('tesouro.categorias.update', $categoria), ['name' => 'Mercado']);

        $this->get(route('tesouro.index'))->assertViewHas('categorias', ['gasto' => [], 'ganho' => []]);
    });

    it('deletes a category without transactions', function () {
        $categoria = FinanceCategory::factory()->create();

        $this->delete(route('tesouro.categorias.destroy', $categoria))->assertSessionHas('sys_toast.title', 'Categoria excluída');

        expect(FinanceCategory::count())->toBe(0);
    });

    it('only deactivates a category that already has transactions', function () {
        $categoria = FinanceCategory::factory()->create();
        Transaction::factory()->create(['finance_category_id' => $categoria->id]);

        $this->delete(route('tesouro.categorias.destroy', $categoria))->assertSessionHas('sys_toast.title', 'Categoria desativada');

        expect($categoria->fresh()->is_active)->toBeFalse()
            ->and(Transaction::count())->toBe(1);
    });
});

it('parses negative amounts', function () {
    expect(Money::parse('-1.234,50'))->toBe('-1234.50')
        ->and(Money::parse("\u{2212}R$ 10"))->toBe('-10');
});
