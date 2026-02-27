<?php

namespace App\Http\Requests\Api\V1\Reminders;

class UpdateReminderSequenceRequest extends StoreReminderSequenceRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        foreach ($rules as $field => $constraints) {
            if (is_array($constraints)) {
                array_unshift($rules[$field], 'sometimes');
            }
        }

        $rules['steps'] = ['sometimes', 'array', 'min:1'];

        return $rules;
    }
}
