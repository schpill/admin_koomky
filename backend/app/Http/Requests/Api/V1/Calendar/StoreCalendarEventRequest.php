<?php

namespace App\Http\Requests\Api\V1\Calendar;

use Illuminate\Foundation\Http\FormRequest;

class StoreCalendarEventRequest extends FormRequest
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
            'calendar_connection_id' => ['nullable', 'uuid', 'exists:calendar_connections,id'],
            'external_id' => ['nullable', 'string', 'max:500'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after_or_equal:start_at'],
            'all_day' => ['sometimes', 'boolean'],
            'location' => ['nullable', 'string', 'max:500'],
            'type' => ['required', 'string', 'in:meeting,deadline,reminder,task,custom'],
            'eventable_type' => ['nullable', 'string', 'max:255'],
            'eventable_id' => ['nullable', 'uuid'],
            'recurrence_rule' => ['nullable', 'string', 'max:500'],
            'sync_status' => ['sometimes', 'string', 'in:local,synced,conflict'],
            'external_updated_at' => ['nullable', 'date'],
        ];
    }
}
