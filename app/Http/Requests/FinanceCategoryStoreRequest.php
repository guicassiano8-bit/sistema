<?php

namespace App\Http\Requests;

use App\Enums\TransactionType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/** Cadastro de categoria pelo modal do Tesouro (bag "categoria"). O nome é único dentro de cada tipo. */
class FinanceCategoryStoreRequest extends FormRequest
{
    protected $errorBag = 'categoria';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(TransactionType::class)],
            'name' => ['required', 'string', 'max:255', $this->uniqueNamePerType($this->input('type'))],
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }

    protected function uniqueNamePerType(?string $type): Unique
    {
        return Rule::unique('finance_categories', 'name')->where('type', $type);
    }

    public function attributes(): array
    {
        return [
            'type' => 'tipo',
            'name' => 'nome',
            'color' => 'cor',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Já existe uma categoria com esse nome.',
            'color.regex' => 'Use uma cor no formato #RRGGBB.',
        ];
    }
}
