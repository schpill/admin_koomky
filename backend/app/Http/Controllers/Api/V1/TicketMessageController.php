<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketMessageController extends Controller
{
    public function index(Ticket $ticket): JsonResponse
    {
        // Placeholder for listing ticket messages
        return response()->json(['message' => 'Ticket messages list', 'ticket_id' => $ticket->id, 'data' => []]);
    }

    public function store(Request $request, Ticket $ticket): JsonResponse
    {
        // Placeholder for creating a new ticket message
        return response()->json(['message' => 'Ticket message created', 'ticket_id' => $ticket->id, 'data' => $request->all()], 201);
    }

    public function update(Request $request, Ticket $ticket, TicketMessage $message): JsonResponse
    {
        // Placeholder for updating a ticket message
        return response()->json(['message' => 'Ticket message updated', 'ticket_id' => $ticket->id, 'message_id' => $message->id, 'data' => $request->all()]);
    }

    public function destroy(Ticket $ticket, TicketMessage $message): JsonResponse
    {
        // Placeholder for deleting a ticket message
        return response()->json(['message' => 'Ticket message deleted', 'ticket_id' => $ticket->id, 'message_id' => $message->id], 204);
    }
}
