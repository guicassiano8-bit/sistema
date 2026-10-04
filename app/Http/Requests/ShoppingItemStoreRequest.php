<?php

namespace App\Http\Requests;

use App\Enums\ShoppingCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Adição rápida e edição de itens do Inventário (bag "inventario"). */
class ShoppingItemStoreRequest extends FormRequest
{
    protected $errorBag = 'inventario';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return self::formRules();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public static function formRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'category' => ['required', Rule::enum(ShoppingCategory::class)],
            'quantity' => 'nullable|integer|min:1|max:9999',
            'unit' => 'nullable|string|max:10',
            'notes' => 'nullable|string|max:2000',
            'scheduled_date' => 'nullable|date_format:Y-m-d',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'category' => 'raridade',
            'quantity' => 'quantidade',
            'unit' => 'unidade',
            'notes' => 'observação',
            'scheduled_date' => 'data da compra',
        ];
    }
}
