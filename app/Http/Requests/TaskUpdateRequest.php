<?php

namespace App\Http\Requests;

use App\Enums\TaskRank;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Formulário completo de missão (missoes.create / missoes.edit).
 * As regras também servem ao store quando o form completo é enviado.
 */
class TaskUpdateRequest extends FormRequest
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
        return self::formRules();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public static function formRules(): array
    {
        return [
            'titulo' => 'required|string|max:120',
            'descricao' => 'nullable|string|max:2000',
            'data' => 'required|date_format:Y-m-d',
            'horario' => 'nullable|date_format:H:i',
            'xp' => 'required|integer|min:0|max:100000',
            'ouro' => 'nullable|integer|min:0|max:100000',
            'rank' => ['required', Rule::enum(TaskRank::class)],
            'recorrencia' => 'nullable|in:nenhuma,diaria,semanal,mensal',
            'recorrencia_dias' => 'exclude_unless:recorrencia,semanal|required|array|min:1',
            'recorrencia_dias.*' => 'integer|between:1,7',
            'recorrencia_dia_mes' => 'exclude_unless:recorrencia,mensal|required|integer|between:1,31',
            'termina_em' => 'nullable|date_format:Y-m-d|after_or_equal:data',
            'aplicar_futuras' => 'nullable|boolean',
        ];
    }

    public function attributes(): array
    {
        return [
            'titulo' => 'missão',
            'recorrencia_dias' => 'dias da semana',
            'recorrencia_dia_mes' => 'dia do mês',
            'termina_em' => 'data final',
        ];
    }
}
