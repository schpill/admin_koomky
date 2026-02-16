<?php

namespace App\Http\Requests\Api\V1\Quotes;

use Illuminate\Validation\Rule;

class UpdateQuoteRequest extends StoreQuoteRequest
{
    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'status' => ['nullable', Rule::in(['draft', 'sent', 'accepted', 'rejected', 'expired'])],
        ];
    }
}
