<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum TaskStatus: string
{
    use EnumHelpers;

    case Pending = 'pending';
    case Done = 'done';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Done => 'Concluída',
            self::Cancelled => 'Cancelada',
        };
    }

    /** Classes Tailwind para o badge de status. */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-100 text-amber-800',
            self::Done => 'bg-emerald-100 text-emerald-800',
            self::Cancelled => 'bg-slate-100 text-slate-500 line-through',
        };
    }
}
