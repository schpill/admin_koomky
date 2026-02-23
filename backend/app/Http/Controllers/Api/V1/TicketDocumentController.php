<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketDocumentController extends Controller
{
    public function index(Ticket $ticket): JsonResponse
    {
        // Placeholder for listing ticket documents
        return response()->json(['message' => 'Ticket documents list', 'ticket_id' => $ticket->id, 'data' => []]);
    }

    public function store(Request $request, Ticket $ticket): JsonResponse
    {
        // Placeholder for uploading a new document and attaching it to the ticket
        return response()->json(['message' => 'Document uploaded and attached', 'ticket_id' => $ticket->id, 'file' => $request->file('document')->getClientOriginalName()], 201);
    }

    public function attach(Request $request, Ticket $ticket): JsonResponse
    {
        // Placeholder for attaching an existing GED document to the ticket
        return response()->json(['message' => 'Existing document attached', 'ticket_id' => $ticket->id, 'document_id' => $request->input('document_id')], 201);
    }

    public function detach(Ticket $ticket, Document $document): JsonResponse
    {
        // Placeholder for detaching a document from the ticket
        return response()->json(['message' => 'Document detached', 'ticket_id' => $ticket->id, 'document_id' => $document->id], 204);
    }
}
