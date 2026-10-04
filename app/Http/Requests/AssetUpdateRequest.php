<?php

namespace App\Http\Requests;

use App\Enums\Indexer;
use App\Http\Requests\Concerns\ParsesMoney;
use App\Models\Asset;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Edição dos dados cadastrais do ativo. Valores e movimentos têm formulários próprios. */
class AssetUpdateRequest extends FormRequest
{
    use ParsesMoney;

    protected $errorBag = 'investimento';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeMoneyFields(['rate']);

        if (is_string($this->input('ticker'))) {
            $this->merge(['ticker' => strtoupper(trim($this->input('ticker')))]);
        }
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
            'name' => 'required|string|max:255',
            'institution' => 'nullable|string|max:255',
            'ticker' => [
                'nullable', Rule::requiredIf($asset->assetType->is_market_traded), 'string', 'max:12',
                Rule::unique('assets', 'ticker')->ignore($asset->id),
            ],
            'indexer' => ['nullable', 'required_with:rate', Rule::enum(Indexer::class)],
            'rate' => 'nullable|required_with:indexer|numeric|decimal:0,4|min:0|max:9999',
            'maturity_date' => 'nullable|date_format:Y-m-d',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function attributes(): array
    {
        return (new AssetStoreRequest)->attributes();
    }

    public function messages(): array
    {
        return (new AssetStoreRequest)->messages();
    }
}
