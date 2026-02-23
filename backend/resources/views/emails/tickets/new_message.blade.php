# New Message on Ticket

Hello,

A new message has been added to Ticket #{!! e($ticket->id) !!} ({!! e($ticket->title) !!}).

**Message:**
{!! e($message->content) !!}

[View Ticket]({!! e(url('/tickets/' . $ticket->id)) !!})
