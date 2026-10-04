<?php

namespace App\Http\Requests;

use App\Enums\AccountType;
use App\Support\Money;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Cadastro de conta pelo modal do Tesouro (bag "conta"). Cartão de crédito chega na Parte 7. */
class AccountStoreRequest extends FormRequest
{
    protected $errorBag = 'conta';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('initial_balance'))) {
            $this->merge(['initial_balance' => Money::parse($this->input('initial_balance'))]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(self::typesAllowed())],
            'institution' => 'nullable|string|max:255',
            'initial_balance' => 'nullable|numeric|decimal:0,2|between:-9999999999999,9999999999999',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function typesAllowed(): array
    {
        return collect(AccountType::cases())
            ->reject(fn (AccountType $tipo) => $tipo->isCreditCard())
            ->map(fn (AccountType $tipo) => $tipo->value)
            ->all();
    }

    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'type' => 'tipo',
            'institution' => 'instituição',
            'initial_balance' => 'saldo inicial',
        ];
    }
}
