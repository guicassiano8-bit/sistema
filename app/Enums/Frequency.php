<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

/**
 * Frequência de tarefas e lançamentos recorrentes.
 * Lançamentos financeiros usam só Monthly e Yearly (validar no Form Request).
 */
enum Frequency: string
{
    use EnumHelpers;

    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    public function label(): string
    {
        return match ($this) {
            self::Daily => 'Diária',
            self::Weekly => 'Semanal',
            self::Monthly => 'Mensal',
            self::Yearly => 'Anual',
        };
    }

    /** Valor de FREQ no padrão RRULE, usado pela biblioteca rlanvin/php-rrule. */
    public function toRRule(): string
    {
        return strtoupper($this->value);
    }

    /** Frequências permitidas em recurring_transactions. */
    public static function forTransactions(): array
    {
        return [self::Monthly, self::Yearly];
    }
}
