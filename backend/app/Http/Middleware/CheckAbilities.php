<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to enforce PAT abilities on API routes.
 *
 * This middleware checks if the authenticated user's token has the required
 * ability for the current action (read vs write operations).
 */
class CheckAbilities
{
    /**
     * Map of HTTP methods to ability prefixes.
     *
     * @var array<string, string>
     */
    protected array $methodAbilityMap = [
        'GET' => 'read',
        'HEAD' => 'read',
        'OPTIONS' => 'read',
        'POST' => 'write',
        'PUT' => 'write',
        'PATCH' => 'write',
        'DELETE' => 'write',
    ];

    /**
     * Map of route segments to entity names for ability checking.
     *
     * @var array<string, string>
     */
    protected array $entityMap = [
        'clients' => 'clients',
        'invoices' => 'invoices',
        'quotes' => 'quotes',
        'credit-notes' => 'invoices',
        'expenses' => 'expenses',
        'expense-categories' => 'expenses',
        'projects' => 'projects',
        'leads' => 'leads',
        'reports' => 'reports',
        'dashboard' => 'reports',
        'segments' => 'clients',
        'campaigns' => 'clients',
        'campaign-templates' => 'clients',
    ];

    /**
     * Routes that bypass ability checks (settings routes).
     *
     * @var array<int, string>
     */
    protected array $bypassRoutes = [
        'settings/profile',
        'settings/business',
        'settings/invoicing',
        'settings/portal',
        'settings/currency',
        'settings/email',
        'settings/sms',
        'settings/notifications',
        'settings/calendar',
        'settings/2fa',
        'settings/accounting',
        'settings/api-tokens',
        'settings/webhooks',
        'auth/me',
        'auth/refresh',
        'auth/logout',
        'auth/2fa',
        'export',
        'import',
        'account',
        'search',
        'activities',
        'currencies',
        'calendar-connections',
        'calendar-events',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Skip if no user (shouldn't happen with auth:sanctum middleware)
        if (! $user) {
            return $next($request);
        }

        // Get the current access token
        $token = $user->currentAccessToken();

        // If no token or not a PersonalAccessToken, user is authenticated via session
        // In this case, allow full access (web UI authentication)
        if (! $token instanceof PersonalAccessToken) {
            return $next($request);
        }

        // Get token abilities
        $abilities = $token->abilities ?? [];

        // If token has wildcard ability (*), allow full access
        if (in_array('*', $abilities, true)) {
            return $next($request);
        }

        // Check if route should bypass ability checking
        $path = $request->path();
        foreach ($this->bypassRoutes as $bypassRoute) {
            if (str_contains($path, $bypassRoute)) {
                return $next($request);
            }
        }

        // Determine required ability
        $requiredAbility = $this->getRequiredAbility($request);

        // If no specific ability required, allow access
        if ($requiredAbility === null) {
            return $next($request);
        }

        // Check if token has the required ability
        if (! in_array($requiredAbility, $abilities, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Token does not have the required ability: '.$requiredAbility,
                'required_ability' => $requiredAbility,
            ], 403);
        }

        return $next($request);
    }

    /**
     * Get the required ability for the current request.
     */
    protected function getRequiredAbility(Request $request): ?string
    {
        $method = $request->method();
        $path = $request->path();

        // Get ability type (read/write) based on HTTP method
        $abilityType = $this->methodAbilityMap[$method] ?? 'read';

        // Extract entity from path
        $entity = $this->extractEntity($path);

        if ($entity === null) {
            return null;
        }

        // Build ability string (e.g., "read:clients", "write:invoices")
        return $abilityType.':'.$entity;
    }

    /**
     * Extract the entity name from the request path.
     */
    protected function extractEntity(string $path): ?string
    {
        // Remove API prefix and version
        $path = preg_replace('#^api/v1/#', '', $path);

        if ($path === null) {
            return null;
        }

        // Get the first segment of the path
        $segments = explode('/', $path);
        $firstSegment = $segments[0];

        // Check for nested routes (e.g., clients/{id}/contacts)
        // In this case, use the parent entity for the first route
        if (count($segments) >= 3 && isset($segments[2])) {
            // Handle nested resources
            // clients/{id}/contacts -> clients (parent resource)
            // projects/{id}/tasks -> projects
            // leads/{id}/activities -> leads
            $parentEntity = $this->entityMap[$firstSegment] ?? null;
            if ($parentEntity !== null) {
                return $parentEntity;
            }
        }

        // Look up entity in map
        return $this->entityMap[$firstSegment] ?? null;
    }
}
