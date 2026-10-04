<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ParsesMoney;
use App\Models\Asset;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/** Valor atual informado à mão: saldo do banco (renda fixa) ou cotação (cotas). Bag "valor". */
class AssetValueRequest extends FormRequest
{
    use ParsesMoney;

    protected $errorBag = 'valor';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeMoneyFields(['value', 'net_balance']);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Asset $asset */
        $asset = $this->route('investimento');
        $asset->loadMissing('assetType');

        return [
            'date' => 'required|date_format:Y-m-d',
            'value' => $asset->assetType->is_market_traded
                ? 'required|numeric|decimal:0,6|gt:0|max:9999999999'
                : 'required|numeric|decimal:0,2|min:0|max:9999999999999',
            'net_balance' => 'nullable|numeric|decimal:0,2|min:0|max:9999999999999',
        ];
    }

    public function attributes(): array
    {
        return [
            'date' => 'data',
            'value' => 'valor',
            'net_balance' => 'saldo líquido',
        ];
    }
}
