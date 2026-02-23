# Ticket Closed

Hello {!! e($ticket->owner->name) !!},

Ticket #{!! e($ticket->id) !!} ({!! e($ticket->title) !!}) has been marked as closed.

[View Ticket]({!! e(url('/tickets/' . $ticket->id)) !!})
