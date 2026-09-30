<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Cria missão por dois caminhos: o modal rápido (regras enxutas, bag
 * "missaoRapida") e o formulário completo, que envia completo=1.
 */
class TaskStoreRequest extends FormRequest
{
    /**
     * Bag usada pelo modal "Nova missão" para reabrir com os erros.
     */
    protected $errorBag = 'missaoRapida';

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /** O formulário completo usa a bag padrão, que o x-sys.input lê sozinho. */
    protected function prepareForValidation(): void
    {
        if ($this->isCompleto()) {
            $this->errorBag = 'default';
        }
    }

    public function isCompleto(): bool
    {
        return $this->boolean('completo');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->isCompleto()) {
            return TaskUpdateRequest::formRules();
        }

        return [
            'titulo' => 'required|string|max:255',
            'xp' => 'required|integer|in:10,30,50,100',
            'data' => 'required|date_format:Y-m-d',
            'recorrencia' => 'nullable|in:diaria,semanal',
        ];
    }
}
