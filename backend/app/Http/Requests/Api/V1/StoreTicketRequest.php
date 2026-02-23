<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\TicketPriority;
use App\Models\Client;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Ticket::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'client_id' => ['nullable', 'uuid', Rule::exists(Client::class, 'id')->where(function ($query) {
                return $query->where('user_id', $this->user()?->id);
            })],
            'project_id' => ['nullable', 'uuid', Rule::exists(Project::class, 'id')->where(function ($query) {
                if ($this->input('client_id')) {
                    $query->where('client_id', $this->input('client_id'));
                }

                return $query->where('user_id', $this->user()?->id);
            })],
            'assigned_to' => ['nullable', 'uuid', Rule::exists(User::class, 'id')],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
            'category' => ['nullable', 'string', 'max:100'],
            'tags' => ['nullable', 'array', 'max:10'],
            'tags.*' => ['string', 'max:50'],
            'deadline' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }
}
