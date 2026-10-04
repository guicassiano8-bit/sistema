<?php

namespace App\Support;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

/** Conversão de dinheiro entre o formato digitado (pt-BR) e o do banco, sem passar por float. */
class Money
{
    /**
     * "1.234,56", "R$ 42,9", "42.90" ou "1.234" → string decimal ("1234.56", "42.9", "42.90", "1234").
     * Retorna o texto original se não parecer um valor, para a validação acusar o erro.
     */
    public static function parse(string $valor): string
    {
        $limpo = trim(str_replace(['R$', "\u{00A0}", ' '], '', $valor));

        if (str_contains($limpo, ',')) {
            $limpo = str_replace(',', '.', str_replace('.', '', $limpo));
        } elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $limpo)) {
            // "1.234" sem vírgula é milhar no Brasil; "42.90" cai no else e continua decimal
            $limpo = str_replace('.', '', $limpo);
        }

        return preg_match('/^\d+(\.\d+)?$/', $limpo) ? $limpo : $valor;
    }

    /** "1234.5" → "R$ 1.234,50". */
    public static function format(string|int|float $valor): string
    {
        $decimal = BigDecimal::of((string) $valor)->toScale(2, RoundingMode::HalfUp);

        return ($decimal->isNegative() ? '−' : '').'R$ '.number_format((float) $decimal->abs()->__toString(), 2, ',', '.');
    }
}
