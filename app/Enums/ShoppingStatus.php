<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum ShoppingStatus: string
{
    use EnumHelpers;

    case Pending = 'pending';
    case Purchased = 'purchased';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'A comprar',
            self::Purchased => 'Comprado',
        };
    }
}
