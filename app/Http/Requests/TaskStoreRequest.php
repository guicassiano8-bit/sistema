<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'titulo' => 'required|string|max:255',
            'xp' => 'required|integer|in:10,30,50,100',
            'data' => 'required|date_format:Y-m-d',
            'recorrencia' => 'nullable|in:diaria,semanal',
        ];
    }
}
