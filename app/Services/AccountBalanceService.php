<?php

namespace App\Services;

use App\Models\Account;
use App\Models\InvestmentTransaction;
use App\Models\Transaction;
use App\Models\Transfer;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\CarbonInterface;

/**
 * Saldo de conta é calculado, nunca salvo: assim editar ou apagar um lançamento
 * não deixa um número desatualizado para trás. Usa BigDecimal para não perder centavos.
 */
class AccountBalanceService
{
    /**
     * Saldo inicial + ganhos pagos − gastos pagos ± transferências − aportes + resgates.
     * Lançamentos pendentes ainda não mexeram no dinheiro, então ficam de fora.
     * Com $ate, é o saldo no fim daquele dia (para comparar com meses anteriores).
     */
    public function saldoDaConta(Account $account, ?CarbonInterface $ate = null): BigDecimal
    {
        $fim = $ate?->copy()->endOfDay();
        $limitar = fn ($query) => $query->when($fim, fn ($q) => $q->where('date', '<=', $fim));

        $ganhos = $this->somar($limitar($account->transactions()->income()->paid())->sum('amount'));
        $gastos = $this->somar($limitar($account->transactions()->expense()->paid())->sum('amount'));
        $recebido = $this->somar($limitar(Transfer::query()->where('to_account_id', $account->id))->sum('amount'));
        $enviado = $this->somar($limitar(Transfer::query()->where('from_account_id', $account->id))->sum('amount'));
        $aportado = $this->somar($limitar(InvestmentTransaction::query()->inflows()->where('account_id', $account->id))->sum('total'));
        $resgatado = $this->somar($limitar(InvestmentTransaction::query()->outflows()->where('account_id', $account->id))->sum('total'));

        return $this->somar($account->initial_balance)
            ->plus($ganhos)->minus($gastos)
            ->plus($recebido)->minus($enviado)
            ->minus($aportado)->plus($resgatado)
            ->toScale(2, RoundingMode::HalfUp);
    }

    /**
     * Soma dos saldos das contas ativas, sem os investimentos. Mesma regra de saldoDaConta(),
     * mas agregada: o número de consultas não cresce com o número de contas.
     */
    public function saldoTotal(?CarbonInterface $ate = null): BigDecimal
    {
        $ids = Account::query()->active()->pluck('id');
        $fim = $ate?->copy()->endOfDay();
        $limitar = fn ($query) => $query->when($fim, fn ($q) => $q->where('date', '<=', $fim));

        $inicial = $this->somar(Account::query()->whereIn('id', $ids)->sum('initial_balance'));
        $ganhos = $this->somar($limitar(Transaction::query()->income()->paid()->whereIn('account_id', $ids))->sum('amount'));
        $gastos = $this->somar($limitar(Transaction::query()->expense()->paid()->whereIn('account_id', $ids))->sum('amount'));
        $recebido = $this->somar($limitar(Transfer::query()->whereIn('to_account_id', $ids))->sum('amount'));
        $enviado = $this->somar($limitar(Transfer::query()->whereIn('from_account_id', $ids))->sum('amount'));
        $aportado = $this->somar($limitar(InvestmentTransaction::query()->inflows()->whereIn('account_id', $ids))->sum('total'));
        $resgatado = $this->somar($limitar(InvestmentTransaction::query()->outflows()->whereIn('account_id', $ids))->sum('total'));

        return $inicial->plus($ganhos)->minus($gastos)
            ->plus($recebido)->minus($enviado)
            ->minus($aportado)->plus($resgatado)
            ->toScale(2, RoundingMode::HalfUp);
    }

    /** O SQLite (testes) devolve float no SUM; arredondar evita ruído como 0.30000000000000004. */
    private function somar(string|int|float|null $valor): BigDecimal
    {
        return BigDecimal::of((string) ($valor ?? 0))->toScale(2, RoundingMode::HalfUp);
    }
}
