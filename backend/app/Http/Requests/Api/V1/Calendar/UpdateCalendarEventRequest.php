<?php

namespace App\Http\Requests\Api\V1\Calendar;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCalendarEventRequest extends FormRequest
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
            'calendar_connection_id' => ['sometimes', 'nullable', 'uuid', 'exists:calendar_connections,id'],
            'external_id' => ['sometimes', 'nullable', 'string', 'max:500'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'start_at' => ['sometimes', 'date'],
            'end_at' => ['sometimes', 'date', 'after_or_equal:start_at'],
            'all_day' => ['sometimes', 'boolean'],
            'location' => ['sometimes', 'nullable', 'string', 'max:500'],
            'type' => ['sometimes', 'string', 'in:meeting,deadline,reminder,task,custom'],
            'eventable_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'eventable_id' => ['sometimes', 'nullable', 'uuid'],
            'recurrence_rule' => ['sometimes', 'nullable', 'string', 'max:500'],
            'sync_status' => ['sometimes', 'string', 'in:local,synced,conflict'],
            'external_updated_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
