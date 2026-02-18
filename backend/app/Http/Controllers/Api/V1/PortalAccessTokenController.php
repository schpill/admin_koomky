<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Mail\PortalInvitationMail;
use App\Models\Client;
use App\Models\PortalAccessToken;
use App\Models\PortalActivityLog;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;

class PortalAccessTokenController extends Controller
{
    use ApiResponse;

    public function index(Client $client): JsonResponse
    {
        Gate::authorize('view', $client);

        $tokens = PortalAccessToken::query()
            ->where('client_id', $client->id)
            ->orderByDesc('created_at')
            ->get();

        return $this->success($tokens, 'Portal access tokens retrieved successfully');
    }

    public function store(Request $request, Client $client): JsonResponse
    {
        Gate::authorize('update', $client);

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'expires_at' => ['nullable', 'date'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $portalAccessToken = PortalAccessToken::query()->create([
            'client_id' => $client->id,
            'email' => (string) $validated['email'],
            'token' => PortalAccessToken::generateToken(),
            'expires_at' => isset($validated['expires_at'])
                ? Carbon::parse((string) $validated['expires_at'])
                : now()->addDays(7),
            'is_active' => true,
            'created_by_user_id' => $user->id,
        ]);

        $magicLink = rtrim((string) config('app.url'), '/').'/portal/auth/verify/'.$portalAccessToken->token;
        Mail::to($portalAccessToken->email)->send(new PortalInvitationMail($client, $magicLink));

        return $this->success($portalAccessToken, 'Portal access token generated successfully', 201);
    }

    public function destroy(Client $client, PortalAccessToken $portalAccessToken): JsonResponse
    {
        Gate::authorize('update', $client);
        $this->ensureBelongsToClient($client, $portalAccessToken);

        $portalAccessToken->forceFill([
            'is_active' => false,
        ])->save();

        return $this->success(null, 'Portal access revoked successfully');
    }

    public function logs(Client $client): JsonResponse
    {
        Gate::authorize('view', $client);

        $logs = PortalActivityLog::query()
            ->where('client_id', $client->id)
            ->orderByDesc('created_at')
            ->paginate(15);

        return $this->success([
            'data' => $logs->items(),
            'current_page' => $logs->currentPage(),
            'per_page' => $logs->perPage(),
            'total' => $logs->total(),
            'last_page' => $logs->lastPage(),
        ], 'Portal activity logs retrieved successfully');
    }

    private function ensureBelongsToClient(Client $client, PortalAccessToken $portalAccessToken): void
    {
        if ($portalAccessToken->client_id !== $client->id) {
            abort(404, 'Portal token not found for this client');
        }
    }
}
