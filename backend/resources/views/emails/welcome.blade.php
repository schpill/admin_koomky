<x-mail::message>
# Welcome to {{ $appName }}!

Hello {{ $user->name }},

Welcome to {{ $appName }}! We're excited to have you on board.

## Getting Started

Your account has been created successfully. You can now sign in using your email address and the password you set during registration.

We recommend taking a few minutes to:

1. **Complete Your Profile** - Add your business information and preferences
2. **Enable Two-Factor Authentication** - Add an extra layer of security to your account
3. **Explore the Dashboard** - Get familiar with the platform

<x-mail::button :url="$setupUrl">
Complete Your Profile
</x-mail::button>

If you have any questions, feel free to reach out to our support team.

Thanks,<br>
{{ $appName }} Team
</x-mail::message>
