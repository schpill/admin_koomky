<?php

namespace App\Http\Requests\Api\V1\Timer;

use Illuminate\Foundation\Http\FormRequest;

class StoreLiveTimerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'task_id' => ['required', 'uuid'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $taskId = $this->task_id;

            if ($taskId) {
                $user = $this->user();
                $task = \App\Models\Task::where('id', $taskId)
                    ->whereHas('project', function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    })
                    ->exists();

                if (!$task) {
                    $validator->errors()->add('task_id', 'The selected task does not exist or does not belong to your projects.');
                }
            }
        });
    }
}