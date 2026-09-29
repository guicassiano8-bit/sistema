<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

/** Indexador de renda fixa. Os valores ficam em maiúsculas, como no mercado. */
enum Indexer: string
{
    use EnumHelpers;

    case Cdi = 'CDI';
    case Ipca = 'IPCA';
    case Pre = 'PRE';

    public function label(): string
    {
        return match ($this) {
            self::Cdi => 'CDI',
            self::Ipca => 'IPCA +',
            self::Pre => 'Prefixado',
        };
    }

    /** Formata a taxa do ativo como o banco mostra: "110% do CDI", "IPCA + 6,5%", "12,3% a.a.". */
    public function formatRate(float $rate): string
    {
        $n = rtrim(rtrim(number_format($rate, 2, ',', '.'), '0'), ',');

        return match ($this) {
            self::Cdi => "{$n}% do CDI",
            self::Ipca => "IPCA + {$n}%",
            self::Pre => "{$n}% a.a.",
        };
    }
}
