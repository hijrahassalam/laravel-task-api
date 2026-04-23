<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:pending,in_progress,done'],
            'priority' => ['sometimes', 'in:low,medium,high'],
            'due_date' => ['nullable', 'date'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ];
    }
}
