<?php

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\FinanceCategory;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Money;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->conta = Account::factory()->wallet()->create();
    $this->mercado = FinanceCategory::factory()->create(['name' => 'Mercado']);
    $this->salario = FinanceCategory::factory()->income()->create(['name' => 'Salário']);
});

function gasto(array $extra = []): array
{
    return $extra + ['type' => 'expense', 'amount' => '42,90', 'date' => '2026-09-10', 'finance_category_id' => test()->mercado->id];
}

it('requires login to launch', function () {
    auth()->logout();

    $this->post(route('tesouro.lancamentos.store'), gasto())->assertRedirect(route('login.index'));
});

describe('creating', function () {
    it('registers an expense with a positive amount and the default account', function () {
        $this->from(route('tesouro.index'))->post(route('tesouro.lancamentos.store'), gasto(['description' => 'Feira']))
            ->assertRedirect(route('tesouro.index'))
            ->assertSessionHas('sys_toast.title', 'Gasto registrado');

        $lancamento = Transaction::sole();
        expect($lancamento->type)->toBe(TransactionType::Expense)
            ->and($lancamento->amount)->toBe('42.90')
            ->and($lancamento->description)->toBe('Feira')
            ->and($lancamento->account_id)->toBe($this->conta->id)
            ->and($lancamento->signed_amount)->toBe('-42.90');
    });

    it('registers income and falls back to the category name as description', function () {
        $this->post(route('tesouro.lancamentos.store'), [
            'type' => 'income', 'amount' => '5.200,00', 'date' => '2026-09-05', 'finance_category_id' => $this->salario->id,
        ])->assertSessionHas('sys_toast.title', 'Ganho registrado');

        expect(Transaction::sole())
            ->amount->toBe('5200.00')
            ->description->toBe('Salário');
    });

    it('accepts the usual ways of typing a Brazilian amount', function (string $digitado, string $esperado) {
        $this->post(route('tesouro.lancamentos.store'), gasto(['amount' => $digitado]));

        expect(Transaction::sole()->amount)->toBe($esperado);
    })->with([
        'com milhar e vírgula' => ['1.234,56', '1234.56'],
        'com R$' => ['R$ 42,9', '42.90'],
        'só inteiro' => ['300', '300.00'],
        'ponto decimal' => ['42.90', '42.90'],
        'milhar sem vírgula' => ['1.234', '1234.00'],
    ]);

    it('rejects a category of the other type, an inactive one and a bad amount', function (Closure $dados, string $campo) {
        $this->post(route('tesouro.lancamentos.store'), $dados() + gasto())
            ->assertSessionHasErrors($campo, errorBag: 'lancamento_gasto');

        expect(Transaction::count())->toBe(0);
    })->with([
        'categoria de ganho num gasto' => [fn () => ['finance_category_id' => test()->salario->id], 'finance_category_id'],
        'categoria inativa' => [fn () => ['finance_category_id' => FinanceCategory::factory()->inactive()->create()->id], 'finance_category_id'],
        'valor zero' => [fn () => ['amount' => '0,00'], 'amount'],
        'valor com texto' => [fn () => ['amount' => 'abc'], 'amount'],
        'mais de 2 casas decimais' => [fn () => ['amount' => '1,234'], 'amount'],
    ]);

    it('reports income errors in the income bag so the right modal reopens', function () {
        $this->post(route('tesouro.lancamentos.store'), ['type' => 'income', 'amount' => '', 'date' => '2026-09-05', 'finance_category_id' => $this->salario->id])
            ->assertSessionHasErrors('amount', errorBag: 'lancamento_ganho');
    });

    it('asks for an account when none exists', function () {
        Account::query()->delete();

        $this->post(route('tesouro.lancamentos.store'), gasto())
            ->assertSessionHasErrors(['account_id' => 'Cadastre uma conta antes de lançar.'], errorBag: 'lancamento_gasto');
    });
});

describe('editing and deleting', function () {
    it('opens the edit page', function () {
        $lancamento = Transaction::factory()->for($this->conta)->create(['finance_category_id' => $this->mercado->id, 'amount' => '42.90']);

        $this->get(route('tesouro.lancamentos.edit', $lancamento))->assertOk()->assertSee('42,90');
    });

    it('updates a transaction and sends the user to that month in the statement', function () {
        $lancamento = Transaction::factory()->for($this->conta)->create(['finance_category_id' => $this->mercado->id, 'date' => '2026-09-10']);

        $this->put(route('tesouro.lancamentos.update', $lancamento), [
            'amount' => '99,99', 'description' => 'Corrigido', 'date' => '2026-08-31',
            'finance_category_id' => $this->mercado->id, 'account_id' => $this->conta->id, 'payment_method' => 'pix',
        ])->assertRedirect(route('tesouro.index', ['aba' => 'extrato', 'mes' => '2026-08']));

        expect($lancamento->fresh())
            ->amount->toBe('99.99')
            ->description->toBe('Corrigido')
            ->type->toBe(TransactionType::Expense);
    });

    it('does not let an expense switch to an income category', function () {
        $lancamento = Transaction::factory()->for($this->conta)->create(['finance_category_id' => $this->mercado->id]);

        $this->put(route('tesouro.lancamentos.update', $lancamento), [
            'amount' => '10', 'date' => '2026-09-10', 'finance_category_id' => $this->salario->id, 'account_id' => $this->conta->id,
        ])->assertSessionHasErrors('finance_category_id', errorBag: 'lancamento');
    });

    it('deletes a transaction', function () {
        $lancamento = Transaction::factory()->for($this->conta)->create(['finance_category_id' => $this->mercado->id]);

        $this->delete(route('tesouro.lancamentos.destroy', $lancamento))->assertRedirect();

        expect(Transaction::count())->toBe(0);
    });
});

describe('statement', function () {
    it('groups by day, totals only paid transactions and ignores other months', function () {
        Transaction::factory()->for($this->conta)->create(['finance_category_id' => $this->mercado->id, 'amount' => '100.10', 'date' => '2026-09-10']);
        Transaction::factory()->for($this->conta)->create(['finance_category_id' => $this->mercado->id, 'amount' => '50.20', 'date' => '2026-09-10']);
        Transaction::factory()->for($this->conta)->pending()->create(['finance_category_id' => $this->mercado->id, 'amount' => '999.00', 'date' => '2026-09-12']);
        Transaction::factory()->income()->for($this->conta)->create(['finance_category_id' => $this->salario->id, 'amount' => '5000.00', 'date' => '2026-09-05']);
        Transaction::factory()->for($this->conta)->create(['finance_category_id' => $this->mercado->id, 'amount' => '777.00', 'date' => '2026-08-31']);

        $this->get(route('tesouro.index', ['aba' => 'extrato', 'mes' => '2026-09']))
            ->assertOk()
            ->assertViewHas('totais', ['entradas' => '5000.00', 'saidas' => '150.30'])
            ->assertViewHas('porDia', fn ($dias) => $dias->keys()->all() === ['2026-09-12', '2026-09-10', '2026-09-05']
                && $dias['2026-09-10']->count() === 2);
    });

    it('filters the list but keeps the month totals', function () {
        Transaction::factory()->for($this->conta)->create(['finance_category_id' => $this->mercado->id, 'amount' => '10.00', 'date' => '2026-09-10']);
        Transaction::factory()->income()->for($this->conta)->create(['finance_category_id' => $this->salario->id, 'amount' => '20.00', 'date' => '2026-09-10']);

        $this->get(route('tesouro.index', ['aba' => 'extrato', 'mes' => '2026-09', 'filtro' => 'ganhos']))
            ->assertViewHas('porDia', fn ($dias) => $dias->flatten()->count() === 1 && $dias->flatten()->first()->type === TransactionType::Income)
            ->assertViewHas('totais', ['entradas' => '20.00', 'saidas' => '10.00']);
    });
});

it('formats and parses money consistently', function () {
    expect(Money::format('1234.5'))->toBe('R$ 1.234,50')
        ->and(Money::format('-5'))->toBe("\u{2212}R$ 5,00")
        ->and(Money::parse('1.234,56'))->toBe('1234.56');
});
