<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum QuoteSource: string
{
    use EnumHelpers;

    case Manual = 'manual';
    case Brapi = 'brapi';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Brapi => 'brapi.dev',
        };
    }
}
