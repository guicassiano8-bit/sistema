<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ParsesMoney;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Rendimento recebido (dividendo, juros) pelo modal dos Ativos (bag "rendimento"). */
class IncomeEntryStoreRequest extends FormRequest
{
    use ParsesMoney;

    protected $errorBag = 'rendimento';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeMoneyFields(['amount']);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'asset_id' => ['required', Rule::exists('assets', 'id')->where('is_active', true)],
            'amount' => 'required|numeric|decimal:0,2|gt:0|max:9999999999999',
            'payment_date' => 'required|date_format:Y-m-d',
        ];
    }

    public function attributes(): array
    {
        return [
            'asset_id' => 'investimento',
            'amount' => 'valor',
            'payment_date' => 'data',
        ];
    }
}
