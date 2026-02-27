<?php

namespace App\Http\Requests\Api\V1\Reminders;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreReminderSequenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.step_number' => ['required', 'integer'],
            'steps.*.delay_days' => ['required', 'integer', 'min:1', 'max:365'],
            'steps.*.subject' => ['required', 'string', 'max:255'],
            'steps.*.body' => ['required', 'string', 'max:10000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $steps = $this->input('steps', []);
            if (! is_array($steps)) {
                return;
            }

            $numbers = array_map(
                static fn (array $step): int => (int) ($step['step_number'] ?? 0),
                array_filter($steps, 'is_array')
            );

            if (count($numbers) !== count(array_unique($numbers))) {
                $validator->errors()->add('steps', 'step_numbers must be unique.');
            }
        });
    }
}
