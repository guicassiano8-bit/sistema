<?php

namespace App\Http\Requests;

use App\Enums\TaskRank;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Cadastro pelo modal da Loja (bag "recompensa"). */
class RewardStoreRequest extends FormRequest
{
    protected $errorBag = 'recompensa';

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
            'description' => 'nullable|string|max:2000',
            'cost' => 'required|integer|min:1|max:10000000',
            'rank' => ['required', Rule::enum(TaskRank::class)],
            'is_repeatable' => 'nullable|boolean',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'recompensa',
            'description' => 'descrição',
            'cost' => 'custo',
        ];
    }
}
