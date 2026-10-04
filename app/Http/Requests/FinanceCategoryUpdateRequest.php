<?php

namespace App\Http\Requests;

use App\Models\FinanceCategory;
use Illuminate\Contracts\Validation\ValidationRule;

/** Edição de categoria. O tipo não muda (os lançamentos dependem dele); inclui o estado "ativa". */
class FinanceCategoryUpdateRequest extends FinanceCategoryStoreRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var FinanceCategory $categoria */
        $categoria = $this->route('categoria');

        return [
            'name' => ['required', 'string', 'max:255', $this->uniqueNamePerType($categoria->type->value)->ignore($categoria->id)],
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active' => 'nullable|boolean',
        ];
    }
}
