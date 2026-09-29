<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum TransactionStatus: string
{
    use EnumHelpers;

    case Paid = 'paid';
    case Pending = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::Paid => 'Pago',
            self::Pending => 'Pendente',
        };
    }
}
