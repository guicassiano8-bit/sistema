<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum ShoppingCategory: string
{
    use EnumHelpers;

    case Daily = 'daily';
    case Urgent = 'urgent';
    case Important = 'important';
    case NotImportant = 'not_important';

    public function label(): string
    {
        return match ($this) {
            self::Daily => 'Dia a dia',
            self::Urgent => 'Urgente',
            self::Important => 'Importante',
            self::NotImportant => 'Não importante',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Urgent => 'bg-red-100 text-red-800',
            self::Important => 'bg-amber-100 text-amber-800',
            self::Daily => 'bg-sky-100 text-sky-800',
            self::NotImportant => 'bg-slate-100 text-slate-600',
        };
    }

    /** Ordem de exibição das colunas/abas: urgente primeiro. */
    public function sortOrder(): int
    {
        return match ($this) {
            self::Urgent => 1,
            self::Important => 2,
            self::Daily => 3,
            self::NotImportant => 4,
        };
    }

    /** Casos já ordenados por sortOrder(). */
    public static function ordered(): array
    {
        $cases = self::cases();
        usort($cases, fn (self $a, self $b) => $a->sortOrder() <=> $b->sortOrder());

        return $cases;
    }
}
