<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\AssignTicketRequest;
use App\Http\Requests\Api\V1\ChangeTicketStatusRequest;
use App\Http\Requests\Api\V1\StoreTicketRequest;
use App\Http\Requests\Api\V1\UpdateTicketRequest;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TicketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Ticket::search($request->input('q', ''))
            ->where('user_id', $user->id);

        // Apply filters
        if ($request->has('filter')) {
            foreach ($request->input('filter') as $key => $value) {
                if ($key === 'overdue' && $value === 'true') {
                    $query->query(function ($q) {
                        $q->where('deadline', '<=', now())
                            ->whereNotIn('status', [
                                \App\Enums\TicketStatus::Resolved->value,
                                \App\Enums\TicketStatus::Closed->value,
                            ]);
                    });
                } else {
                    $query->where($key, $value);
                }
            }
        }

        // Apply sorting
        if ($request->has('sort')) {
            $sort = $request->input('sort');
            $direction = 'asc';
            if (str_starts_with($sort, '-')) {
                $direction = 'desc';
                $sort = substr($sort, 1);
            }
            $query->orderBy($sort, $direction);
        }

        $tickets = $query->paginate(20);

        return response()->json([
            'message' => 'Tickets list',
            'filters' => $request->only(['status', 'priority', 'client_id', 'assigned_to', 'category', 'tags', 'date_from', 'date_to', 'deadline_from', 'deadline_to', 'overdue']),
            'data' => $tickets,
        ]);
    }

    public function store(StoreTicketRequest $request): JsonResponse
    {
        // Placeholder for creating a new ticket
        return response()->json(['message' => 'Ticket created', 'data' => $request->all()], 201);
    }

    public function show(Ticket $ticket): JsonResponse
    {
        // Placeholder for showing a specific ticket
        return response()->json(['message' => 'Ticket details', 'data' => $ticket]);
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): JsonResponse
    {
        // Placeholder for updating a ticket
        return response()->json(['message' => 'Ticket updated', 'data' => $request->all(), 'ticket_id' => $ticket->id]);
    }

    public function destroy(Ticket $ticket): JsonResponse
    {
        // Placeholder for deleting a ticket
        return response()->json(['message' => 'Ticket deleted', 'ticket_id' => $ticket->id], 204);
    }

    public function changeStatus(ChangeTicketStatusRequest $request, Ticket $ticket): JsonResponse
    {
        $ticket->status = $request->validated('status');
        $ticket->save();

        return response()->json(['message' => 'Ticket status changed', 'ticket_id' => $ticket->id, 'new_status' => $ticket->status->value]);
    }

    public function assign(AssignTicketRequest $request, Ticket $ticket): JsonResponse
    {
        $ticket->assigned_to = $request->validated('assigned_to');
        $ticket->save();

        return response()->json(['message' => 'Ticket assigned', 'ticket_id' => $ticket->id, 'assigned_to' => $ticket->assigned_to]);
    }

    public function stats(): JsonResponse
    {
        $userId = auth()->id();

        $allTickets = Ticket::where('user_id', $userId)->get();

        $totalTickets = $allTickets->count();

        $totalOpen = $allTickets->where('status', \App\Enums\TicketStatus::Open)->count();
        $totalInProgress = $allTickets->where('status', \App\Enums\TicketStatus::InProgress)->count();
        $totalPending = $allTickets->where('status', \App\Enums\TicketStatus::Pending)->count();
        $totalResolved = $allTickets->where('status', \App\Enums\TicketStatus::Resolved)->count();
        $totalClosed = $allTickets->where('status', \App\Enums\TicketStatus::Closed)->count();

        $totalLowPriority = $allTickets->where('priority', \App\Enums\TicketPriority::Low)->count();
        $totalNormalPriority = $allTickets->where('priority', \App\Enums\TicketPriority::Normal)->count();
        $totalHighPriority = $allTickets->where('priority', \App\Enums\TicketPriority::High)->count();
        $totalUrgentPriority = $allTickets->where('priority', \App\Enums\TicketPriority::Urgent)->count();

        $totalOverdue = $allTickets->filter(fn (Ticket $ticket) => $ticket->isOverdue())->count();

        $resolvedTickets = Ticket::where('user_id', $userId)
            ->where('status', \App\Enums\TicketStatus::Resolved)
            ->whereNotNull('resolved_at')
            ->get();

        $averageResolutionTimeInHours = 0;
        if ($resolvedTickets->count() > 0) {
            $totalResolutionTime = 0;
            foreach ($resolvedTickets as $ticket) {
                if ($ticket->created_at && $ticket->resolved_at) {
                    $totalResolutionTime += $ticket->created_at->diffInHours($ticket->resolved_at);
                }
            }
            $averageResolutionTimeInHours = $totalResolutionTime / $resolvedTickets->count();
        }

        return response()->json(['message' => 'Ticket statistics', 'data' => [
            'total_tickets' => $totalTickets,
            'total_open' => $totalOpen,
            'total_in_progress' => $totalInProgress,
            'total_pending' => $totalPending,
            'total_resolved' => $totalResolved,
            'total_closed' => $totalClosed,
            'total_low_priority' => $totalLowPriority,
            'total_normal_priority' => $totalNormalPriority,
            'total_high_priority' => $totalHighPriority,
            'total_urgent_priority' => $totalUrgentPriority,
            'total_overdue' => $totalOverdue,
            'average_resolution_time_in_hours' => round($averageResolutionTimeInHours, 2),
        ]]);
    }

    public function overdue(): JsonResponse
    {
        // Placeholder for overdue tickets list
        return response()->json(['message' => 'Overdue tickets', 'data' => []]);
    }
}
