<?php

namespace App\Http\Requests\UserSettings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore(auth()->id()),
            ],
            'business_name' => ['sometimes', 'string', 'max:255'],
            'business_address' => ['sometimes', 'string', 'max:1000'],
            'siret' => ['sometimes', 'string', 'max:14'],
            'ape_code' => ['sometimes', 'string', 'max:6'],
            'vat_number' => ['sometimes', 'string', 'max:20'],
            'default_payment_terms' => ['sometimes', 'integer', 'min:1', 'max:365'],
            'invoice_footer' => ['sometimes', 'string', 'max:1000'],
        ];
    }
}
