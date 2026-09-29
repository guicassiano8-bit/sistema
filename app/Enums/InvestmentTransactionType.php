<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

/**
 * Buy/Sell: ativos com cotas (FII, ações). Exigem quantity e unit_price.
 * Deposit/Withdrawal: renda fixa (CDB, Tesouro). Só total.
 */
enum InvestmentTransactionType: string
{
    use EnumHelpers;

    case Buy = 'buy';
    case Sell = 'sell';
    case Deposit = 'deposit';
    case Withdrawal = 'withdrawal';

    public function label(): string
    {
        return match ($this) {
            self::Buy => 'Compra',
            self::Sell => 'Venda',
            self::Deposit => 'Aporte',
            self::Withdrawal => 'Resgate',
        };
    }

    /** true = dinheiro sai da conta e entra no investimento. */
    public function isInflow(): bool
    {
        return in_array($this, [self::Buy, self::Deposit], true);
    }

    /** Operações que exigem quantity e unit_price. */
    public function requiresQuantity(): bool
    {
        return in_array($this, [self::Buy, self::Sell], true);
    }

    /** Vendas gravam average_cost e realized_profit. */
    public function realizesProfit(): bool
    {
        return $this === self::Sell;
    }
}
