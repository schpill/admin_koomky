<?php

namespace App\Http\Requests\Api\V1\Tasks;

use Illuminate\Foundation\Http\FormRequest;

class StoreTimeEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'description' => ['nullable', 'string'],
        ];
    }
}
