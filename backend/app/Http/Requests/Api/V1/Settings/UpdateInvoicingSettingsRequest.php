<?php

namespace App\Http\Requests\Api\V1\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoicingSettingsRequest extends FormRequest
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
            'payment_terms_days' => ['required', 'integer', 'min:1', 'max:365'],
            'bank_details' => ['nullable', 'string'],
            'invoice_footer' => ['nullable', 'string'],
            'invoice_numbering_pattern' => ['required', 'string', 'max:50'],
        ];
    }
}
