<?php

namespace Dcp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DcpMicroserviceAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        if (!$this->hasValidMicroserviceKey($request)) {
            abort(401, 'Unauthorized: invalid or missing X-DCPMicroservice-Key.');
        }

        return $next($request);
    }

    private function hasValidMicroserviceKey(Request $request): bool
    {
        $incomingKey = $request->header('X-DCPMicroservice-Key');
        $configuredKey = config('dcp.environment.core_key');

        if (!is_string($incomingKey) || $incomingKey === '') {
            return false;
        }

        if (!is_string($configuredKey) || $configuredKey === '') {
            return false;
        }

        return hash_equals($configuredKey, $incomingKey);
    }
}
