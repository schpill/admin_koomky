<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #374151;
            background-color: #f3f4f6;
            margin: 0;
            padding: 20px;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        .email-header {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            padding: 30px;
            text-align: center;
        }
        .email-header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .email-body {
            padding: 30px;
        }
        .email-body h1 {
            font-size: 22px;
            color: #111827;
            margin-top: 0;
            margin-bottom: 16px;
        }
        .email-body h2 {
            font-size: 18px;
            color: #111827;
            margin-top: 24px;
            margin-bottom: 12px;
        }
        .email-body p {
            margin-bottom: 16px;
            color: #4b5563;
        }
        .email-body ul {
            margin-bottom: 16px;
            padding-left: 24px;
            color: #4b5563;
        }
        .email-body li {
            margin-bottom: 8px;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .email-button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #3b82f6;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
        }
        .email-button:hover {
            background-color: #2563eb;
        }
        .email-footer {
            background-color: #f9fafb;
            padding: 20px 30px;
            text-align: center;
            font-size: 14px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
        }
        .email-footer p {
            margin: 0;
        }
        .highlight {
            background-color: #fef3c7;
            padding: 12px;
            border-left: 4px solid #f59e0b;
            margin: 16px 0;
        }
        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
            }
            .email-body {
                padding: 20px !important;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>{{ config('app.name') }}</h1>
        </div>
        <div class="email-body">
            {{ $slot }}
        </div>
        <div class="email-footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            <p style="margin-top: 8px;">
                This email was sent to {{ $user->email }}.
            </p>
        </div>
    </div>
</body>
</html>
