<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum PaymentMethod: string
{
    use EnumHelpers;

    case Pix = 'pix';
    case Debit = 'debit';
    case Credit = 'credit';
    case Cash = 'cash';
    case BankSlip = 'bank_slip';
    case Transfer = 'transfer';

    public function label(): string
    {
        return match ($this) {
            self::Pix => 'Pix',
            self::Debit => 'Débito',
            self::Credit => 'Crédito',
            self::Cash => 'Dinheiro',
            self::BankSlip => 'Boleto',
            self::Transfer => 'TED/DOC',
        };
    }

    /** Só compras no crédito podem ser parceladas. */
    public function allowsInstallments(): bool
    {
        return $this === self::Credit;
    }
}
