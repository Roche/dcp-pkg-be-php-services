<?php

namespace Dcp\Guard;

use Dcp\Models\User;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;

class JwtBearerGuard implements Guard
{
    use GuardHelpers;

    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function user(): ?User
    {
        if (!$this->request->headers->has('Authorization')) {
            return null;
        }

        if (!is_null($this->user)) {
            return $this->user;
        }

        $this->user = $this->processJwt();

        return $this->user;
    }

    private function processJwt(): ?User
    {
        try {
            $authHeader = trim(str_replace('Bearer', '', $this->request->header('Authorization')));

            if (!$authHeader) {
                return null;
            }

            $jwt = JWT::decode($authHeader, new Key(config('dcp.environment.jwt_token'), 'HS256'));

            if (!$this->isValidPersistedGrant($jwt)) {
                return null;
            }

            return $this->createUserFromJwt($jwt);
        } catch (ExpiredException $e) {
            abort(
                (new JsonResponse(null, Response::HTTP_UNAUTHORIZED))
                    ->formatJson('error', $e->getMessage())
            );
        } catch (\Exception $e) {
            abort(
                (new JsonResponse(null, Response::HTTP_INTERNAL_SERVER_ERROR))
                    ->formatJson('error', $e->getMessage())
            );
        }
    }

    private function createUserFromJwt(object $jwt): User
    {
        $user = new User();
        $user->username = $jwt->{'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/name'} ?? null;
        $user->id = isset($jwt->currentLoggedUserId) ? (int)$jwt->currentLoggedUserId : null;
        $user->scopes = $jwt->permissions ?? [];
        $user->displayName = $jwt->displayName ?? null;
        $user->persistedGrantsType = $jwt->persistedGrantsType ?? null;

        return $user;
    }

    private function isValidPersistedGrant(object $jwt): bool
    {
        if ((int) $jwt->persistedGrantsType === 2) {
            $dcpGatewayKey = $this->request->header('X-DCPGateway-Key');
            return $dcpGatewayKey && $dcpGatewayKey === config('dcp.environment.gateway_key');
        }

        return true;
    }

    public function validate(array $credentials = []): void
    {
        //Not required
    }
}
