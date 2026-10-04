<?php

namespace App\Http\Requests\Concerns;

use App\Support\Money;

/** Converte campos digitados em pt-BR ("1.234,56") para o formato decimal antes da validação. */
trait ParsesMoney
{
    /**
     * @param  array<int, string>  $campos
     */
    protected function normalizeMoneyFields(array $campos): void
    {
        foreach ($campos as $campo) {
            if (is_string($this->input($campo)) && $this->input($campo) !== '') {
                $this->merge([$campo => Money::parse($this->input($campo))]);
            }
        }
    }
}
