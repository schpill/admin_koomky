<?php

namespace App\Http\Requests\Api\V1\Clients;

use App\Support\InputSanitizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('clients', 'email')->ignore($this->route('client')),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'preferred_currency' => ['nullable', 'string', 'size:3'],
            'notes' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'archived', 'inactive'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $sanitized = InputSanitizer::sanitizeFields($this->all(), [
            'name',
            'phone',
            'address',
            'city',
            'zip_code',
            'country',
            'preferred_currency',
            'notes',
        ]);

        if (isset($sanitized['preferred_currency']) && is_string($sanitized['preferred_currency'])) {
            $sanitized['preferred_currency'] = strtoupper($sanitized['preferred_currency']);
        }

        $this->merge($sanitized);
    }
}
