<x-mail::message>
# Bonjour,

Vous avez reçu un document de la part de notre plateforme.

**Titre du document :** {{ $document->title }}

@if($customMessage)
{{ $customMessage }}
@endif

Le document est joint à cet e-mail.

Merci,<br>
{{ config('app.name') }}
</x-mail::message>
