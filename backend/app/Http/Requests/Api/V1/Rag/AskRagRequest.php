<?php

namespace App\Http\Requests\Api\V1\Rag;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AskRagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'max:1000'],
            'client_id' => [
                'nullable',
                'uuid',
                Rule::exists('clients', 'id')->where(function ($query): void {
                    $query->where('user_id', $this->user()->id);
                }),
            ],
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        if ($this->filled('client_id')) {
            $hasClientError = array_key_exists('client_id', $validator->errors()->toArray());
            if ($hasClientError) {
                abort(403, 'Client non autorisé.');
            }
        }

        parent::failedValidation($validator);
    }
}
