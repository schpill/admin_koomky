<?php

namespace App\Http\Requests\UserSettings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBusinessRequest extends FormRequest
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
            'business_name' => ['sometimes', 'string', 'max:255'],
            'business_address' => ['sometimes', 'string', 'max:1000'],
            'siret' => ['sometimes', 'string', 'size:14'],
            'ape_code' => ['sometimes', 'string', 'size:6'],
            'vat_number' => ['sometimes', 'string', 'max:20'],
            'default_payment_terms' => ['sometimes', 'integer', 'min:1', 'max:365'],
            'invoice_footer' => ['sometimes', 'string', 'max:1000'],
        ];
    }
}
