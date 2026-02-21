<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Client;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('ticket'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'client_id' => ['sometimes', 'nullable', 'uuid', Rule::exists(Client::class, 'id')->where(function ($query) {
                return $query->where('user_id', $this->user()->id);
            })],
            'project_id' => ['sometimes', 'nullable', 'uuid', Rule::exists(Project::class, 'id')->where(function ($query) {
                if ($this->input('client_id')) {
                    $query->where('client_id', $this->input('client_id'));
                }
                return $query->where('user_id', $this->user()->id);
            })],
            'assigned_to' => ['sometimes', 'nullable', 'uuid', Rule::exists(User::class, 'id')],
            'priority' => ['sometimes', Rule::enum(TicketPriority::class)],
            'category' => ['sometimes', 'nullable', 'string', 'max:100'],
            'tags' => ['sometimes', 'nullable', 'array', 'max:10'],
            'tags.*' => ['string', 'max:50'],
            'deadline' => ['sometimes', 'nullable', 'date', 'after_or_equal:today'],
        ];
    }
}
