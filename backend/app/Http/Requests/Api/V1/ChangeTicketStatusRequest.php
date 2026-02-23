<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeTicketStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('changeStatus', $this->route('ticket')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Ticket $ticket */
        $ticket = $this->route('ticket');

        return [
            'status' => ['required', Rule::enum(TicketStatus::class), function ($attribute, $value, $fail) use ($ticket) {
                $newStatus = TicketStatus::from($value);
                $currentStatus = $ticket->status;

                $validTransitions = match ($currentStatus) {
                    TicketStatus::Open => [TicketStatus::InProgress, TicketStatus::Pending, TicketStatus::Resolved, TicketStatus::Closed],
                    TicketStatus::InProgress => [TicketStatus::Open, TicketStatus::Pending, TicketStatus::Resolved, TicketStatus::Closed],
                    TicketStatus::Pending => [TicketStatus::Open, TicketStatus::InProgress, TicketStatus::Resolved, TicketStatus::Closed],
                    TicketStatus::Resolved => [TicketStatus::Open, TicketStatus::Closed],
                    TicketStatus::Closed => [TicketStatus::Open],
                };

                if (! in_array($newStatus, $validTransitions)) {
                    $fail('Invalid status transition from '.$currentStatus->value.' to '.$newStatus->value);
                }

                // Additional rule: Only owner can close or reopen
                if (in_array($newStatus, [TicketStatus::Closed, TicketStatus::Open]) && $this->user()?->id !== $ticket->user_id) {
                    $fail('Only the ticket owner can close or reopen a ticket.');
                }
            }],
        ];
    }
}
