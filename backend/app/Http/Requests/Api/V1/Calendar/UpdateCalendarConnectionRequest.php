<?php

namespace App\Http\Requests\Api\V1\Calendar;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCalendarConnectionRequest extends FormRequest
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
            'provider' => ['sometimes', 'string', 'in:google,caldav'],
            'name' => ['sometimes', 'string', 'max:255'],
            'credentials' => ['sometimes', 'array'],
            'calendar_id' => ['sometimes', 'nullable', 'string', 'max:500'],
            'sync_enabled' => ['sometimes', 'boolean'],
        ];
    }
}
