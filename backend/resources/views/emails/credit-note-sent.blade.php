<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Credit Note {{ $creditNote->number }}</title>
</head>
<body>
    <p>Hello {{ $creditNote->client?->name }},</p>

    <p>Your credit note <strong>{{ $creditNote->number }}</strong> is attached as PDF.</p>

    <p>
        Related invoice: {{ $creditNote->invoice?->number }}<br>
        Total: {{ number_format((float) $creditNote->total, 2, '.', '') }} {{ $creditNote->currency }}
    </p>

    <p>Thank you.</p>
</body>
</html>
