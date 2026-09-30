<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

/** Dificuldade da missão. Só visual: não altera XP nem ouro. */
enum TaskRank: string
{
    use EnumHelpers;

    case E = 'E';
    case D = 'D';
    case C = 'C';
    case B = 'B';
    case A = 'A';
    case S = 'S';

    public function label(): string
    {
        return "Rank {$this->value}";
    }
}
