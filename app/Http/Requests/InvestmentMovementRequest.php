<?php

namespace App\Http\Requests;

use App\Enums\InvestmentTransactionType;
use App\Http\Requests\Concerns\ParsesMoney;
use App\Models\Asset;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Aporte/resgate (renda fixa) ou compra/venda (cotas) de um ativo (bag "movimento"). */
class InvestmentMovementRequest extends FormRequest
{
    use ParsesMoney;

    protected $errorBag = 'movimento';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeMoneyFields(['total', 'unit_price', 'fees']);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Asset $asset */
        $asset = $this->route('investimento');
        $asset->loadMissing('assetType');
        $comCotas = $asset->assetType->is_market_traded;

        $tipos = $comCotas
            ? [InvestmentTransactionType::Buy, InvestmentTransactionType::Sell]
            : [InvestmentTransactionType::Deposit, InvestmentTransactionType::Withdrawal];

        return [
            'type' => ['required', Rule::in(array_map(fn (InvestmentTransactionType $t) => $t->value, $tipos))],
            'date' => 'required|date_format:Y-m-d',
            'account_id' => ['nullable', Rule::exists('accounts', 'id')->where('is_active', true)],
        ] + ($comCotas ? [
            'quantity' => 'required|integer|min:1|max:99999999',
            'unit_price' => 'required|numeric|decimal:0,6|gt:0|max:9999999999',
            'fees' => 'nullable|numeric|decimal:0,2|min:0|max:9999999999',
        ] : [
            'total' => 'required|numeric|decimal:0,2|gt:0|max:9999999999999',
        ]);
    }

    public function attributes(): array
    {
        return [
            'type' => 'operação',
            'date' => 'data',
            'account_id' => 'conta',
            'quantity' => 'cotas',
            'unit_price' => 'preço por cota',
            'fees' => 'taxas',
            'total' => 'valor',
        ];
    }
}
