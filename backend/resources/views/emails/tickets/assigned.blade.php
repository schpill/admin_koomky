# Ticket Assigned

Hello {!! e($ticket->assignee->name) !!},

Ticket #{!! e($ticket->id) !!} ({!! e($ticket->title) !!}) has been assigned to you.

[View Ticket]({!! e(url('/tickets/' . $ticket->id)) !!})
