<x-mail::message>
# Reset Your Password

Hello {{ $user->name }},

We received a request to reset your password for your {{ config('app.name') }} account.

Click the button below to reset your password. This link will expire in {{ $expiresIn }} minutes.

<x-mail::button :url="$resetUrl">
Reset Password
</x-mail::button>

If you did not request a password reset, please ignore this email or contact support if you have questions.

Thanks,<br>
{{ config('app.name') }} Team
</x-mail::message>
