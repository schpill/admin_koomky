<?php

namespace App\Http\Requests\Api\V1\Leads;

use App\Support\InputSanitizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50', 'regex:/^\+[1-9]\d{1,14}$/'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'source' => ['sometimes', Rule::in(['manual', 'referral', 'website', 'campaign', 'other'])],
            'status' => ['sometimes', Rule::in(['new', 'contacted', 'qualified', 'proposal_sent', 'negotiating', 'won', 'lost'])],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'probability' => ['nullable', 'integer', 'min:0', 'max:100'],
            'expected_close_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'The phone number must be in E.164 format (e.g., +33612345678).',
        ];
    }

    protected function prepareForValidation(): void
    {
        $sanitized = InputSanitizer::sanitizeFields($this->all(), [
            'first_name',
            'last_name',
            'email',
            'phone',
            'company_name',
            'notes',
        ]);

        if (isset($sanitized['currency']) && is_string($sanitized['currency'])) {
            $sanitized['currency'] = strtoupper($sanitized['currency']);
        }

        $this->merge($sanitized);
    }
}
