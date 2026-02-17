<?php

namespace App\Http\Requests\Api\V1\Calendar;

use Illuminate\Foundation\Http\FormRequest;

class StoreCalendarConnectionRequest extends FormRequest
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
            'provider' => ['required', 'string', 'in:google,caldav'],
            'name' => ['required', 'string', 'max:255'],
            'credentials' => ['required', 'array'],
            'calendar_id' => ['nullable', 'string', 'max:500'],
            'sync_enabled' => ['sometimes', 'boolean'],
        ];
    }
}
