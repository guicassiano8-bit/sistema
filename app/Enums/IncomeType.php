<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum IncomeType: string
{
    use EnumHelpers;

    case Dividend = 'dividend';
    case Interest = 'interest';
    case Jcp = 'jcp';

    public function label(): string
    {
        return match ($this) {
            self::Dividend => 'Dividendo / rendimento de FII',
            self::Interest => 'Juros',
            self::Jcp => 'JCP',
        };
    }
}
