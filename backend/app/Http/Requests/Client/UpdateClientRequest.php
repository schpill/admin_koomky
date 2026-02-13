<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateClientRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:255'],
            'vat_number' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'billing_address' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', 'in:active,archived'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'contacts' => ['nullable', 'array'],
            'contacts.*.name' => ['required_with:contacts', 'string', 'max:255'],
            'contacts.*.email' => ['nullable', 'email', 'max:255'],
            'contacts.*.phone' => ['nullable', 'string', 'max:50'],
            'contacts.*.position' => ['nullable', 'string', 'max:255'],
            'contacts.*.is_primary' => ['nullable', 'boolean'],
        ];
    }
}
