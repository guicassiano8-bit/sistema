<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum PointCurrency: string
{
    use EnumHelpers;

    case Xp = 'xp';
    case Gold = 'gold';

    public function label(): string
    {
        return match ($this) {
            self::Xp => 'XP',
            self::Gold => 'Ouro',
        };
    }
}
