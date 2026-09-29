<?php

namespace App\Enums\Concerns;

/**
 * Métodos comuns a todos os Enums do sistema.
 * Cada Enum que usa este trait precisa implementar label().
 */
trait EnumHelpers
{
    abstract public function label(): string;

    /** Lista de valores, útil para validação: Rule::in(TaskStatus::values()). */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** [valor => rótulo], pronto para montar um <select> no Blade. */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
