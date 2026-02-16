<?php

namespace App\Http\Requests\Api\V1\Projects;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
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
            'description' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in([
                'draft',
                'proposal_sent',
                'in_progress',
                'on_hold',
                'completed',
                'cancelled',
            ])],
            'billing_type' => ['required', Rule::in(['hourly', 'fixed'])],
            'hourly_rate' => ['nullable', 'numeric', 'min:0', 'required_if:billing_type,hourly'],
            'fixed_price' => ['nullable', 'numeric', 'min:0', 'required_if:billing_type,fixed'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'deadline' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }
}
