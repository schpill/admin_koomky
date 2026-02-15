<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTwoFactorAuthentication
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->two_factor_confirmed_at) {
            /** @var \Laravel\Sanctum\PersonalAccessToken $token */
            $token = $request->user()->currentAccessToken();
            
            if ($token && $token->can('2fa-pending')) {
                // If the request is not to the 2FA verification endpoint itself
                if (!$request->is('api/v1/auth/2fa/verify') && !$request->is('api/v1/auth/logout')) {
                    return response()->json([
                        'status' => 'Error',
                        'message' => 'Two-factor authentication required.',
                        'code' => '2FA_REQUIRED'
                    ], 403);
                }
            }
        }

        return $next($request);
    }
}
