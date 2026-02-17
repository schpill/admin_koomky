<?php

namespace App\Http\Requests\Api\V1\Segments;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSegmentRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_dynamic' => ['nullable', 'boolean'],
            'filters' => ['required', 'array'],
            'filters.group_boolean' => ['nullable', Rule::in(['and', 'or'])],
            'filters.criteria_boolean' => ['nullable', Rule::in(['and', 'or'])],
            'filters.groups' => ['required', 'array', 'min:1'],
            'filters.groups.*.criteria' => ['required', 'array', 'min:1'],
            'filters.groups.*.criteria.*.type' => ['required', 'string', Rule::in([
                'tag',
                'last_interaction',
                'project_status',
                'revenue',
                'location',
                'created_at',
                'custom',
                'custom_field',
            ])],
            'filters.groups.*.criteria.*.operator' => ['required', 'string', 'max:50'],
            'filters.groups.*.criteria.*.field' => ['nullable', 'string', 'max:50'],
            'filters.groups.*.criteria.*.value' => ['nullable'],
        ];
    }
}
