<?php

namespace App\Services;

class EmailTrackingTokenService
{
    public function encode(string $recipientId): string
    {
        $signature = hash_hmac('sha256', $recipientId, (string) config('app.key'));

        return $recipientId.'.'.$signature;
    }

    public function decode(string $token): ?string
    {
        $parts = explode('.', $token, 2);

        if (count($parts) !== 2) {
            return null;
        }

        [$recipientId, $signature] = $parts;
        $expected = hash_hmac('sha256', $recipientId, (string) config('app.key'));

        if (! hash_equals($expected, $signature)) {
            return null;
        }

        return $recipientId;
    }
}
