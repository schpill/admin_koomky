<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Mail\PortalInvitationMail;
use App\Models\Client;
use App\Models\PortalAccessToken;
use App\Services\PortalActivityLogger;
use App\Services\PortalSessionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class PortalAuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly PortalSessionService $portalSessionService,
        private readonly PortalActivityLogger $portalActivityLogger,
    ) {}

    public function requestMagicLink(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        /** @var Client|null $client */
        $client = Client::query()
            ->where('email', $validated['email'])
            ->first();

        if (! $client) {
            return $this->success(null, 'If the email exists, a magic link has been sent.');
        }

        $portalAccessToken = PortalAccessToken::query()->create([
            'client_id' => $client->id,
            'email' => (string) $validated['email'],
            'token' => PortalAccessToken::generateToken(),
            'expires_at' => now()->addDays(7),
            'is_active' => true,
            'created_by_user_id' => $client->user_id,
        ]);

        $magicLink = rtrim((string) config('app.url'), '/').'/portal/auth/verify/'.$portalAccessToken->token;

        Mail::to($validated['email'])->send(new PortalInvitationMail($client, $magicLink));

        return $this->success(null, 'If the email exists, a magic link has been sent.');
    }

    public function verify(Request $request, string $token): JsonResponse
    {
        /** @var PortalAccessToken|null $portalAccessToken */
        $portalAccessToken = PortalAccessToken::query()
            ->with('client')
            ->active()
            ->notExpired()
            ->where('token', $token)
            ->first();

        if (! $portalAccessToken || ! $portalAccessToken->client) {
            return $this->error('Portal token is invalid or expired', 401);
        }

        /** @var Client $client */
        $client = $portalAccessToken->client;

        $portalAccessToken->forceFill([
            'last_used_at' => now(),
        ])->save();

        $portalToken = $this->portalSessionService->createSession($portalAccessToken);

        $this->portalActivityLogger->log(
            $request,
            $client,
            'login',
            $portalAccessToken
        );

        return $this->success([
            'portal_token' => $portalToken,
            'expires_in' => Carbon::now()->addHours(8)->diffInSeconds(now()),
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
            ],
        ], 'Portal access granted');
    }

    public function logout(Request $request): JsonResponse
    {
        $sessionToken = (string) $request->attributes->get('portal_session_token', '');
        if ($sessionToken !== '') {
            $this->portalSessionService->invalidateSession($sessionToken);
        }

        $client = $request->attributes->get('portal_client');
        $portalAccessToken = $request->attributes->get('portal_access_token');

        if ($client instanceof Client && $portalAccessToken instanceof PortalAccessToken) {
            $this->portalActivityLogger->log(
                $request,
                $client,
                'logout',
                $portalAccessToken
            );
        }

        return $this->success(null, 'Portal session closed');
    }
}
