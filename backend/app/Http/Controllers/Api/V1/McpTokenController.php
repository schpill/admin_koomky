<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class McpTokenController extends Controller
{
    use ApiResponse;

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 401);
        $token = $user->createToken('mcp-read', ['mcp:read']);

        return $this->success([
            'token' => $token->plainTextToken,
            'abilities' => ['mcp:read'],
        ], 'MCP token created', 201);
    }
}
