<p>Hello {{ $client->name }},</p>
<p>You can access your client portal using this secure magic link:</p>
<p><a href="{{ $magicLink }}">{{ $magicLink }}</a></p>
<p>This link will expire automatically.</p>
