<?php

namespace App\Http\Requests\Api\V1\ProjectTemplates;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectTemplateRequest extends FormRequest
{
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'billing_type' => ['nullable', 'in:hourly,fixed'],
            'default_hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'default_currency' => ['nullable', 'string', 'size:3'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'tasks' => ['nullable', 'array'],
            'tasks.*.title' => ['required_with:tasks', 'string', 'max:255'],
            'tasks.*.description' => ['nullable', 'string', 'max:2000'],
            'tasks.*.estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'tasks.*.priority' => ['nullable', 'in:low,medium,high,urgent'],
            'tasks.*.sort_order' => ['nullable', 'integer'],
        ];
    }
}
