<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

/** Edição de conta: as regras do cadastro mais o estado "ativa". */
class AccountUpdateRequest extends AccountStoreRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return parent::rules() + ['is_active' => 'nullable|boolean'];
    }
}
