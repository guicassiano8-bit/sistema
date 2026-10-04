<?php

namespace App\Http\Requests;

use App\Models\Transaction;
use App\Support\Money;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/** Edição de um lançamento (bag "lancamento"). O tipo não muda: gasto continua gasto. */
class TransactionUpdateRequest extends FormRequest
{
    protected $errorBag = 'lancamento';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('amount'))) {
            $this->merge(['amount' => Money::parse($this->input('amount'))]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Transaction $lancamento */
        $lancamento = $this->route('lancamento');

        return TransactionStoreRequest::formRules($lancamento->type->value);
    }

    public function attributes(): array
    {
        return TransactionStoreRequest::attributeNames();
    }

    public function messages(): array
    {
        return (new TransactionStoreRequest)->messages();
    }
}
