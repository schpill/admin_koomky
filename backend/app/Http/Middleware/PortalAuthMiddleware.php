<?php

namespace App\Http\Middleware;

use App\Models\PortalSettings;
use App\Services\PortalSessionService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalAuthMiddleware
{
    public function __construct(
        private readonly PortalSessionService $portalSessionService
    ) {}

    /**
     * @param  Closure(Request): mixed  $next
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $sessionToken = $this->extractBearerToken($request);
        if ($sessionToken === null) {
            return $this->error('Portal authentication required', 401);
        }

        $portalAccessToken = $this->portalSessionService->resolveSession($sessionToken);
        if (! $portalAccessToken) {
            return $this->error('Portal session is invalid or expired', 401);
        }

        $client = $portalAccessToken->client;
        $user = $client?->user;
        if (! $client || ! $user) {
            return $this->error('Portal session is invalid', 401);
        }

        $settings = PortalSettings::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['portal_enabled' => false]
        );

        if (! $settings->portal_enabled) {
            return $this->error('Portal access is disabled', 403);
        }

        $portalAccessToken->forceFill([
            'last_used_at' => now(),
        ])->save();

        $request->attributes->set('portal_client', $client);
        $request->attributes->set('portal_access_token', $portalAccessToken);
        $request->attributes->set('portal_session_token', $sessionToken);
        $request->attributes->set('portal_settings', $settings);

        return $next($request);
    }

    private function extractBearerToken(Request $request): ?string
    {
        $header = $request->header('Authorization');
        if (! is_string($header) || ! str_starts_with($header, 'Bearer ')) {
            return null;
        }

        $token = trim(substr($header, 7));

        return $token === '' ? null : $token;
    }

    private function error(string $message, int $status): JsonResponse
    {
        return response()->json([
            'status' => 'Error',
            'message' => $message,
            'data' => null,
        ], $status, [], JSON_PRESERVE_ZERO_FRACTION);
    }
}
