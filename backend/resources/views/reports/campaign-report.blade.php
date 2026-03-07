<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Campaign Report</title>
</head>
<body>
    <h1>{{ $campaign->name }}</h1>
    <p>Sent: {{ $report['summary']['sent'] }}</p>
    <p>Opened: {{ $report['summary']['opened'] }}</p>
    <p>Clicked: {{ $report['summary']['clicked'] }}</p>

    <h2>Links</h2>
    <ul>
        @foreach ($report['links'] as $link)
            <li>{{ $link['url'] }} - {{ $link['total_clicks'] }}</li>
        @endforeach
    </ul>

    <h2>Timeline</h2>
    <ul>
        @foreach ($report['timeline'] as $point)
            <li>{{ $point['date'] }} - opens: {{ $point['opens'] }}, clicks: {{ $point['clicks'] }}</li>
        @endforeach
    </ul>
</body>
</html>
