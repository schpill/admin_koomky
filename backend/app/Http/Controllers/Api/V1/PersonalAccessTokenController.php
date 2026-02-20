<?php

namespace App\Http\Controllers\Api\V1\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Rule;

class PersonalAccessTokenController extends Controller
{
    use ApiResponse;

    /**
     * Available token abilities (scopes).
     */
    private const AVAILABLE_SCOPES = [
        'read:clients',
        'write:clients',
        'read:invoices',
        'write:invoices',
        'read:expenses',
        'write:expenses',
        'read:projects',
        'read:leads',
        'write:leads',
        'read:reports',
    ];

    /**
     * List personal access tokens.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $tokens = $user->tokens()
            ->where('name', 'not like', 'portal_%')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($token): array => [
                'id' => $token->id,
                'name' => $token->name,
                'abilities' => $token->abilities,
                'last_used_at' => $token->last_used_at?->toIso8601String(),
                'expires_at' => $token->expires_at?->toIso8601String(),
                'created_at' => $token->created_at?->toIso8601String(),
            ]);

        return $this->success([
            'data' => $tokens,
            'available_scopes' => self::AVAILABLE_SCOPES,
        ], 'API tokens retrieved successfully');
    }

    /**
     * Create a new personal access token.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => ['string', Rule::in(self::AVAILABLE_SCOPES)],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $expiresAt = isset($validated['expires_at'])
            ? Date::parse($validated['expires_at'])
            : null;

        $token = $user->createToken(
            $validated['name'],
            $validated['abilities'],
            $expiresAt
        );

        return $this->success([
            'id' => $token->accessToken->id,
            'name' => $token->accessToken->name,
            'abilities' => $token->accessToken->abilities,
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
            'token' => $token->plainTextToken, // Only shown once
            'created_at' => $token->accessToken->created_at?->toIso8601String(),
        ], 'API token created successfully. Copy the token now - it will not be shown again.', 201);
    }

    /**
     * Delete (revoke) a personal access token.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $token = $user->tokens()
            ->where('id', $id)
            ->firstOrFail();

        $token->delete();

        return $this->success(null, 'API token revoked successfully');
    }

    /**
     * Get available scopes.
     */
    public function scopes(): JsonResponse
    {
        return $this->success([
            'scopes' => array_map(fn ($scope): array => [
                'name' => $scope,
                'description' => $this->getScopeDescription($scope),
            ], self::AVAILABLE_SCOPES),
        ], 'Available scopes retrieved successfully');
    }

    /**
     * Get human-readable description for a scope.
     */
    private function getScopeDescription(string $scope): string
    {
        return match ($scope) {
            'read:clients' => 'Read client information',
            'write:clients' => 'Create, update, and delete clients',
            'read:invoices' => 'Read invoices and credit notes',
            'write:invoices' => 'Create, update, and delete invoices',
            'read:expenses' => 'Read expense records',
            'write:expenses' => 'Create, update, and delete expenses',
            'read:projects' => 'Read project information',
            'read:leads' => 'Read lead information',
            'write:leads' => 'Create, update, and delete leads',
            'read:reports' => 'Read reports and analytics',
            default => $scope,
        };
    }
}
