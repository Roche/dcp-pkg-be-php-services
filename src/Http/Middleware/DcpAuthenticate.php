<?php

namespace Dcp\Http\Middleware;

use Closure;
use Exception;
use Dcp\Helpers\CurrentUser;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class DcpAuthenticate extends Middleware
{
    public function handle($request, Closure $next, ...$scopes)
    {
        // Separate guards from scopes
        $guards = [];
        $accessibleScopes = array_filter($scopes, function ($scope) use (&$guards) {
            if (config("auth.guards.$scope")) {
                $guards[] = $scope;
                return false;
            }
            return true;
        });

        // Authenticate user first
        $this->authenticate($request, $guards);

        // Get authenticated user
        $user = $this->auth->user();

        // Early return: no scopes required, grant access
        if (empty($accessibleScopes)) {
            return $next($request);
        }

        $accessibleScopes = array_map('strtoupper', $accessibleScopes);
        $userScopes = collect($user->scopes);

        // Route to appropriate handler based on grant type
        $hasAccess = match ($user->persistedGrantsType) {
            1 => $this->handleActualUser($accessibleScopes, $userScopes, $request),
            2 => throw new Exception("Grant type 2 is not supported", 1),
            3 => $this->hasRequiredPermission(
                $userScopes,
                $accessibleScopes
            ),
            default => false,
        };

        if (!$hasAccess) {
            abort(403, 'Access denied: insufficient permissions for this resource.');
        }

        return $next($request);
    }

    protected function handleActualUser(array $accessibleScopes, Collection $userScopes, Request $request): bool
    {
        $currentSiteId = $request->query('siteId', $request->query('site_id'));

        // Early return: admin bypass
        if (!is_null($currentSiteId) && CurrentUser::isSiteAdmin($currentSiteId)) {
            return true;
        }

        if (is_numeric($currentSiteId) && (int)$currentSiteId === 0 && CurrentUser::isPlatformAdmin()) {
            return true;
        }

        // Require siteId for user authentication
        if (is_null($currentSiteId)) {
            return false;
        }

        return $this->hasRequiredPermission($userScopes, $accessibleScopes, (int)$currentSiteId);
    }

    private function hasRequiredPermission(
        Collection $userScopes,
        array $requiredScopes,
        ?int $siteId = null
    ): bool {
        return $userScopes->contains(function ($scope) use ($requiredScopes, $siteId) {
            $hasModule = $scope->Module === config('dcp.module_slug');
            $hasPermission = in_array(strtoupper($scope->Permission), $requiredScopes);
            $hasSite = $siteId === null || (int)$scope->SiteId === $siteId;

            return $hasModule && $hasPermission && $hasSite;
        });
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param Request $request
     *
     * @return never
     */
    protected function redirectTo(Request $request): never
    {
        // Return a 401 Unauthorized response for JSON requests
        abort((new JsonResponse(null, Response::HTTP_UNAUTHORIZED))->formatJson('error', 'Unauthorized'));
    }
}
