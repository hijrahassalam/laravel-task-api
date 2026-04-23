<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'in:pending,in_progress,done'],
            'priority' => ['nullable', 'in:low,medium,high'],
            'due_date' => ['nullable', 'date', 'after:today'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ];
    }
}
