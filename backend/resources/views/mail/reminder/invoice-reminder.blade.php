<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Relance facture</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f8;font-family:Arial,sans-serif;color:#1f2937;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="640" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border-radius:8px;overflow:hidden;border:1px solid #e5e7eb;">
                <tr>
                    <td style="background:#0f172a;color:#ffffff;padding:20px 24px;font-size:20px;font-weight:700;">
                        Rappel de paiement
                    </td>
                </tr>
                <tr>
                    <td style="padding:24px;font-size:14px;line-height:1.6;">
                        {!! $body !!}
                    </td>
                </tr>
                @if($payLink)
                    <tr>
                        <td style="padding:0 24px 24px;">
                            <a href="{{ $payLink }}" style="display:inline-block;background:#16a34a;color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:6px;font-weight:600;">
                                Régler maintenant
                            </a>
                        </td>
                    </tr>
                @endif
                <tr>
                    <td style="padding:16px 24px;border-top:1px solid #e5e7eb;font-size:12px;color:#6b7280;">
                        Ce message est une relance automatique liée à votre facture {{ $invoice->number }}.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
