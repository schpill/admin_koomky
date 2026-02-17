<?php

namespace App\Http\Requests\Api\V1\RecurringInvoices;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecurringInvoiceProfileRequest extends FormRequest
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
            'client_id' => [
                'required',
                'uuid',
                Rule::exists('clients', 'id')->where(function ($query): void {
                    $query->where('user_id', $this->user()?->id);
                }),
            ],
            'name' => ['required', 'string', 'max:255'],
            'frequency' => ['required', Rule::in([
                'weekly',
                'biweekly',
                'monthly',
                'quarterly',
                'semiannual',
                'annual',
            ])],
            'start_date' => ['required', 'date'],
            'next_due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'day_of_month' => ['nullable', 'integer', 'min:1', 'max:31'],
            'line_items' => ['required', 'array', 'min:1'],
            'line_items.*.description' => ['required', 'string', 'max:255'],
            'line_items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'line_items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'line_items.*.vat_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
            'payment_terms_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'paused', 'completed', 'cancelled'])],
            'max_occurrences' => ['nullable', 'integer', 'min:1'],
            'auto_send' => ['nullable', 'boolean'],
            'currency' => ['nullable', 'string', 'size:3'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('currency')) {
            $this->merge([
                'currency' => strtoupper((string) $this->input('currency')),
            ]);
        }
    }
}
