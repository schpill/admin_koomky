<?php

namespace App\Http\Controllers\Api\V1\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PersonalAccessTokenController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $tokens = $user->tokens()->latest()->get();

        return $this->success($tokens, 'API tokens retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'abilities' => 'required|array|min:1',
            'expires_at' => 'nullable|date|after:now',
        ]);

        /** @var User $user */
        $user = $request->user();

        $token = $user->createToken(
            $request->name,
            $request->abilities,
            $request->expires_at ? now()->parse($request->expires_at) : null
        );

        return $this->success([
            'token' => $token->plainTextToken,
        ], 'API token created successfully', 201);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $token = $user->tokens()->where('id', $id)->first();

        if (! $token) {
            return $this->error('Token not found', 404);
        }

        $token->delete();

        return $this->success(null, 'API token revoked successfully');
    }

    public function scopes(): JsonResponse
    {
        $scopes = [
            ['name' => 'read:clients', 'description' => 'Read client information'],
            ['name' => 'write:clients', 'description' => 'Create, update, and delete clients'],
            ['name' => 'read:invoices', 'description' => 'Read invoices and credit notes'],
            ['name' => 'write:invoices', 'description' => 'Create, update, and delete invoices'],
            ['name' => 'read:expenses', 'description' => 'Read expense records'],
            ['name' => 'write:expenses', 'description' => 'Create, update, and delete expenses'],
            ['name' => 'read:projects', 'description' => 'Read project information'],
            ['name' => 'read:leads', 'description' => 'Read lead information'],
            ['name' => 'write:leads', 'description' => 'Create, update, and delete leads'],
            ['name' => 'read:reports', 'description' => 'Read reports and analytics'],
        ];

        return $this->success($scopes, 'Available scopes retrieved successfully');
    }
}
