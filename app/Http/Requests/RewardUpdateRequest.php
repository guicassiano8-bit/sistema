<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/** Edição no formulário completo: as regras do cadastro mais o estado "ativa". */
class RewardUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return RewardStoreRequest::formRules() + ['is_active' => 'nullable|boolean'];
    }

    public function attributes(): array
    {
        return (new RewardStoreRequest)->attributes();
    }
}
