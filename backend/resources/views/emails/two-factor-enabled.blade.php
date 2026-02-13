<x-mail::message>
# Two-Factor Authentication Enabled

Hello {{ $user->name }},

Two-factor authentication has been successfully enabled on your {{ $appName }} account.

## What This Means

Your account is now more secure. When you sign in, you'll be asked to enter a code from your authenticator app.

## Important: Save Your Recovery Codes

We've generated 8 recovery codes for you. These codes can be used to access your account if you lose access to your authenticator device.

**Keep these codes in a safe place.** Each code can only be used once.

If you didn't enable two-factor authentication, please contact support immediately.

Thanks,<br>
{{ $appName }} Team
</x-mail::message>
