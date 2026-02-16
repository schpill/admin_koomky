<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->number }}</title>
</head>
<body>
    <p>Hello {{ $invoice->client?->name }},</p>

    <p>Your invoice <strong>{{ $invoice->number }}</strong> is attached as PDF.</p>

    <p>
        Amount due: {{ number_format((float) $invoice->total, 2, '.', '') }} {{ $invoice->currency }}<br>
        Due date: {{ $invoice->due_date?->toDateString() }}
    </p>

    <p>Thank you.</p>
</body>
</html>
