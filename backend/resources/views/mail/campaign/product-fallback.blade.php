<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $product_name ?? 'Notre offre' }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .header {
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        h1 {
            color: #1f2937;
            font-size: 24px;
            margin: 0 0 10px 0;
        }
        .price {
            font-size: 28px;
            color: #4f46e5;
            font-weight: bold;
            margin: 20px 0;
        }
        .cta-button {
            display: inline-block;
            background-color: #4f46e5;
            color: #ffffff;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            color: #6b7280;
        }
        .unsubscribe {
            color: #6b7280;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Découvrez notre offre exceptionnelle</h1>
        </div>

        <p>Bonjour {{ $first_name ?? 'cher client' }},</p>

        <p>Nous sommes ravis de vous présenter <strong>{{ $product_name ?? 'notre nouvelle offre' }}</strong>.</p>

        <div class="price">
            {{ $product_price ?? 'Sur demande' }}
        </div>

        <p>Cette offre a été spécialement pensée pour {{ $company ?? 'votre entreprise' }} et vos besoins.</p>

        <p>N'hésitez pas à nous contacter pour plus d'informations ou pour bénéficier de cette offre.</p>

        <center>
            <a href="#" class="cta-button">En savoir plus</a>
        </center>

        <p>Cordialement,<br>
        L'équipe</p>

        <div class="footer">
            <p>Si vous ne souhaitez plus recevoir nos communications, vous pouvez <a href="{{ $unsubscribe_link ?? '#' }}" class="unsubscribe">vous désinscrire ici</a>.</p>
        </div>
    </div>
</body>
</html>
