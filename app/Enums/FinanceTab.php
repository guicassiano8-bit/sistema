<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

/** Abas da tela do Tesouro. O valor é o que vai em ?aba= e o nome do arquivo em tesouro/abas/. */
enum FinanceTab: string
{
    use EnumHelpers;

    case Summary = 'resumo';
    case Statement = 'extrato';
    case Assets = 'ativos';
    case Reports = 'relatorios';

    public function label(): string
    {
        return match ($this) {
            self::Summary => 'Resumo',
            self::Statement => 'Extrato',
            self::Assets => 'Ativos',
            self::Reports => 'Relatórios',
        };
    }
}
