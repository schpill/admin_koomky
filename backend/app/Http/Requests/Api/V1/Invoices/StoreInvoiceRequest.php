<?php

namespace App\Http\Requests\Api\V1\Invoices;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
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
            'project_id' => [
                'nullable',
                'uuid',
                Rule::exists('projects', 'id')->where(function ($query): void {
                    $query->where('user_id', $this->user()?->id);
                }),
            ],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'currency' => ['nullable', 'string', 'size:3'],
            'notes' => ['nullable', 'string'],
            'discount_type' => ['nullable', Rule::in(['percentage', 'fixed'])],
            'discount_value' => ['nullable', 'numeric', 'min:0', 'required_with:discount_type'],
            'line_items' => ['required', 'array', 'min:1'],
            'line_items.*.description' => ['required', 'string', 'max:255'],
            'line_items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'line_items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'line_items.*.vat_rate' => ['required', 'numeric', Rule::in([0, 5.5, 10, 20])],
        ];
    }
}
