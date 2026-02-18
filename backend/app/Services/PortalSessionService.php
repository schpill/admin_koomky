<?php

namespace App\Services;

use App\Models\PortalAccessToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PortalSessionService
{
    public function createSession(PortalAccessToken $portalAccessToken): string
    {
        $sessionToken = Str::random(80);

        Cache::put(
            $this->key($sessionToken),
            $portalAccessToken->id,
            now()->addHours(8)
        );

        return $sessionToken;
    }

    public function resolveSession(string $sessionToken): ?PortalAccessToken
    {
        /** @var string|null $portalAccessTokenId */
        $portalAccessTokenId = Cache::get($this->key($sessionToken));
        if (! is_string($portalAccessTokenId) || $portalAccessTokenId === '') {
            return null;
        }

        return PortalAccessToken::query()
            ->with('client.user')
            ->active()
            ->notExpired()
            ->find($portalAccessTokenId);
    }

    public function invalidateSession(string $sessionToken): void
    {
        Cache::forget($this->key($sessionToken));
    }

    private function key(string $sessionToken): string
    {
        return 'portal:session:'.$sessionToken;
    }
}
