<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quote {{ $quote->number }}</title>
</head>
<body>
    <p>Hello {{ $quote->client?->name }},</p>

    <p>Your quote <strong>{{ $quote->number }}</strong> is attached as PDF.</p>

    <p>
        Total: {{ number_format((float) $quote->total, 2, '.', '') }} {{ $quote->currency }}<br>
        Valid until: {{ $quote->valid_until?->toDateString() }}
    </p>

    <p>Thank you.</p>
</body>
</html>
