<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Credit Note {{ $creditNote->number }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111827; }
        h1 { font-size: 18px; margin: 0 0 8px; }
        h2 { font-size: 14px; margin: 18px 0 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: left; }
        th { background: #f3f4f6; }
        .totals td { font-weight: bold; }
        .meta { margin-top: 6px; }
    </style>
</head>
<body>
    <h1>Credit Note {{ $creditNote->number }}</h1>

    <div class="meta">
        <strong>Issue date:</strong> {{ $creditNote->issue_date?->toDateString() }}<br>
        <strong>Status:</strong> {{ $creditNote->status }}<br>
        <strong>Invoice:</strong> {{ $creditNote->invoice?->number }}
    </div>

    <h2>Client</h2>
    <div>{{ $creditNote->client?->name }}</div>
    <div>{{ $creditNote->client?->email }}</div>

    <h2>Line Items</h2>
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>VAT %</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($creditNote->lineItems as $lineItem)
                <tr>
                    <td>{{ $lineItem->description }}</td>
                    <td>{{ number_format((float) $lineItem->quantity, 2, '.', '') }}</td>
                    <td>{{ number_format((float) $lineItem->unit_price, 2, '.', '') }}</td>
                    <td>{{ number_format((float) $lineItem->vat_rate, 2, '.', '') }}</td>
                    <td>{{ number_format((float) $lineItem->total, 2, '.', '') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tbody>
            <tr>
                <td>Subtotal</td>
                <td>{{ number_format((float) $creditNote->subtotal, 2, '.', '') }}</td>
            </tr>
            <tr>
                <td>Tax</td>
                <td>{{ number_format((float) $creditNote->tax_amount, 2, '.', '') }}</td>
            </tr>
            <tr>
                <td>Total</td>
                <td>{{ number_format((float) $creditNote->total, 2, '.', '') }}</td>
            </tr>
        </tbody>
    </table>

    @if ($creditNote->reason)
        <h2>Reason</h2>
        <div>{{ $creditNote->reason }}</div>
    @endif
</body>
</html>
