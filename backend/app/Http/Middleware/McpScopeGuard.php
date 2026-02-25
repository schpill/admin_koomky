<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class McpScopeGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $token = $user->currentAccessToken();

        if (! $token instanceof PersonalAccessToken) {
            return response()->json(['message' => 'MCP token required'], 403);
        }

        $abilities = $token->abilities ?? [];
        if (! in_array('*', $abilities, true) && ! in_array('mcp:read', $abilities, true)) {
            return response()->json(['message' => 'Missing mcp:read scope'], 403);
        }

        return $next($request);
    }
}
