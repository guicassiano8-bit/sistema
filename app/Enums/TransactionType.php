<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

/** Tipo de lançamento e de categoria financeira. */
enum TransactionType: string
{
    use EnumHelpers;

    case Income = 'income';
    case Expense = 'expense';

    public function label(): string
    {
        return match ($this) {
            self::Income => 'Ganho',
            self::Expense => 'Gasto',
        };
    }

    /** amount é sempre positivo no banco; o sinal no cálculo do saldo vem daqui. */
    public function sign(): int
    {
        return $this === self::Income ? 1 : -1;
    }

    public function textClasses(): string
    {
        return $this === self::Income ? 'text-emerald-600' : 'text-red-600';
    }
}
