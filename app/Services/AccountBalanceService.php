<?php

namespace App\Services;

use App\Models\Account;
use App\Models\InvestmentTransaction;
use App\Models\Transfer;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

/**
 * Saldo de conta é calculado, nunca salvo: assim editar ou apagar um lançamento
 * não deixa um número desatualizado para trás. Usa BigDecimal para não perder centavos.
 */
class AccountBalanceService
{
    /**
     * Saldo inicial + ganhos pagos − gastos pagos ± transferências − aportes + resgates.
     * Lançamentos pendentes ainda não mexeram no dinheiro, então ficam de fora.
     */
    public function saldoDaConta(Account $account): BigDecimal
    {
        $ganhos = $this->somar($account->transactions()->income()->paid()->sum('amount'));
        $gastos = $this->somar($account->transactions()->expense()->paid()->sum('amount'));
        $recebido = $this->somar(Transfer::query()->where('to_account_id', $account->id)->sum('amount'));
        $enviado = $this->somar(Transfer::query()->where('from_account_id', $account->id)->sum('amount'));
        $aportado = $this->somar(InvestmentTransaction::query()->inflows()->where('account_id', $account->id)->sum('total'));
        $resgatado = $this->somar(InvestmentTransaction::query()->outflows()->where('account_id', $account->id)->sum('total'));

        return $this->somar($account->initial_balance)
            ->plus($ganhos)->minus($gastos)
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
