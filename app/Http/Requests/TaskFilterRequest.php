<?php

namespace App\Http\Requests;

use App\Enums\TaskRank;
use App\Enums\TaskStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskFilterRequest extends FormRequest
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
        return [
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(TaskStatus::values())],
            'rank' => ['nullable', Rule::in(TaskRank::values())],
            'tipo' => ['nullable', Rule::in(['avulsa', 'recorrente'])],
        ];
    }
}
