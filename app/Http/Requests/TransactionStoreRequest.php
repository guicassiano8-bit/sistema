<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Support\Money;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Lançamento de gasto ou ganho pelos modais do Tesouro (bags "lancamento_gasto" e "lancamento_ganho"). */
class TransactionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->errorBag = $this->input('type') === TransactionType::Income->value
            ? 'lancamento_ganho'
            : 'lancamento_gasto';

        $this->merge(array_filter([
            'amount' => is_string($this->input('amount')) ? Money::parse($this->input('amount')) : null,
            // sem conta escolhida, vale a primeira conta ativa (a Carteira, no seeder)
            'account_id' => $this->input('account_id') ?: Account::query()->active()->orderBy('id')->value('id'),
        ], fn ($valor) => $valor !== null));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(TransactionType::class)],
            ...self::formRules($this->input('type')),
        ];
    }

    /**
     * Regras compartilhadas com a edição. A categoria precisa ser ativa e do mesmo tipo do lançamento.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public static function formRules(?string $type): array
    {
        return [
            'description' => 'nullable|string|max:255',
            'amount' => 'required|numeric|decimal:0,2|gt:0|max:9999999999999',
            'date' => 'required|date_format:Y-m-d',
            'finance_category_id' => ['required', Rule::exists('finance_categories', 'id')
                ->where('is_active', true)->where('type', $type)],
            'account_id' => ['required', Rule::exists('accounts', 'id')->where('is_active', true)],
            'payment_method' => ['nullable', Rule::enum(PaymentMethod::class)],
        ];
    }

    public function attributes(): array
    {
        return self::attributeNames();
    }

    /**
     * @return array<string, string>
     */
    public static function attributeNames(): array
    {
        return [
            'description' => 'descrição',
            'amount' => 'valor',
            'date' => 'data',
            'finance_category_id' => 'categoria',
            'account_id' => 'conta',
            'payment_method' => 'forma de pagamento',
        ];
    }

    public function messages(): array
    {
        return [
            'account_id.required' => 'Cadastre uma conta antes de lançar.',
            'finance_category_id.required' => 'Escolha uma categoria.',
            'finance_category_id.exists' => 'Escolha uma categoria válida para este tipo de lançamento.',
        ];
    }
}
