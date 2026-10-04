<?php

namespace App\Http\Requests;

use App\Enums\Indexer;
use App\Http\Requests\Concerns\ParsesMoney;
use App\Services\InvestmentService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Cadastro de investimento pelo modal dos Ativos (bag "investimento"). */
class AssetStoreRequest extends FormRequest
{
    use ParsesMoney;

    protected $errorBag = 'investimento';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeMoneyFields(['amount', 'rate']);

        if (is_string($this->input('ticker'))) {
            $this->merge(['ticker' => strtoupper(trim($this->input('ticker')))]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(InvestmentService::TYPES)],
            'name' => 'required|string|max:255',
            'institution' => 'nullable|string|max:255',
            'ticker' => ['nullable', 'required_if:type,FII', 'string', 'max:12', Rule::unique('assets', 'ticker')],
            'indexer' => ['nullable', 'required_with:rate', Rule::enum(Indexer::class)],
            'rate' => 'nullable|required_with:indexer|numeric|decimal:0,4|min:0|max:9999',
            'maturity_date' => 'nullable|date_format:Y-m-d',
            'amount' => 'required|numeric|decimal:0,2|gt:0|max:9999999999999',
            'date' => 'required|date_format:Y-m-d',
            'quantity' => 'nullable|required_if:type,FII|integer|min:1|max:99999999',
            'account_id' => ['nullable', Rule::exists('accounts', 'id')->where('is_active', true)],
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => 'tipo',
            'name' => 'nome',
            'institution' => 'instituição',
            'ticker' => 'ticker',
            'indexer' => 'indexador',
            'rate' => 'taxa',
            'maturity_date' => 'vencimento',
            'amount' => 'valor aplicado',
            'date' => 'data',
            'quantity' => 'cotas',
            'account_id' => 'conta',
        ];
    }

    public function messages(): array
    {
        return [
            'ticker.unique' => 'Já existe um ativo com esse ticker.',
        ];
    }
}
