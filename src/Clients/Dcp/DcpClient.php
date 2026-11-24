<?php

namespace Dcp\Clients\Dcp;

use Dcp\Contracts\DcpInterface;
use Dcp\Helpers\CurrentUser;
use Dcp\Helpers\Json;
use Dcp\Traits\FormatsDcpClientResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use JsonException;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;

const TOKEN_STARTER = 'Bearer ';
const DCP_TOKEN_JSON = 'dcp-token.json';

class DcpClient implements DcpInterface
{
    use FormatsDcpClientResponse;

    protected ?string $accessToken = null; // Stores the access token used for authorization
    protected ?Client $client = null; // HTTP client for API requests
    protected ?string $authMethod = null; // Authentication method ('user' or 'server')

    /**
     * DcpClient constructor. Initializes the HTTP client and determines the authentication method.
     *
     * @param string|null $authMethod Optional authentication method override.
     */
    public function __construct(?string $authMethod = null)
    {
        $this->authMethod = $authMethod ?? (CurrentUser::id() ? 'user' : 'server');

        // Initialize the Guzzle client with the base URI and SSL verification settings
        $this->client = new Client([
            'base_uri' => config('dcp.internal_gateway_url'),
            'verify'   => config('dcp.security.verify_ssl'),
        ]);

        // Determine and perform the appropriate authentication
        $this->authMethod === 'user' ? $this->userAuth() : $this->serverAuth();
    }

    /**
     * Retrieves data from an endpoint.
     *
     * @param string $endpoint Endpoint key defined in configuration.
     * @param array $data Optional data for the request.
     *
     * @return mixed Parsed response data or null if none.
     * @throws JsonException If JSON decoding fails.
     */
    public function getData(string $endpoint, array $data = []): mixed
    {
        // Retrieve endpoint configuration
        $endpointDefinition = config("dcp.endpoints.$endpoint")
            ?? abort_with_data(404, 'Endpoint ("' . $endpoint . '") not found.');

        return $this->call($endpointDefinition, $data) ?? null;
    }

    /**
     * Asynchronously retrieves data from an endpoint.
     *
     * @param string $endpoint Endpoint key defined in configuration.
     * @param array $data Optional data for the request.
     *
     * @return PromiseInterface A promise that resolves to the parsed response data or null if none.
     * @throws JsonException If JSON decoding fails during promise resolution.
     */
    public function getDataAsync(string $endpoint, array $data = []): PromiseInterface
    {
        // Retrieve endpoint configuration (abort synchronously if missing/unknown)
        $endpointDefinition = config("dcp.endpoints.$endpoint")
            ?? abort_with_data(404, 'Endpoint ("' . $endpoint . '") not found.');

        return $this->callAsync($endpointDefinition, $data);
    }

    /**
     * Makes an HTTP call to a configured endpoint.
     *
     * @param string $endpoint Endpoint configuration key.
     * @param array|null $data Data to send with the request.
     *
     * @return array The response data, headers, and status.
     * @throws JsonException If JSON decoding fails.
     */
    private function call(array $endpointDefinition, ?array $data = null): mixed
    {
        $this->validateParams($endpointDefinition, $data);

        [$requestMethod, $url, $requestData] = $this->buildRequest($endpointDefinition, $data);

        try {
            $response = $this->$requestMethod($url, $requestData);
            $responseBody = (string) $response->getBody();

            return $this->parseResponse($endpointDefinition, $responseBody);
        } catch (ClientException $ex) {
            Log::error('DCP client request failed.', $this->buildErrorContext($endpointDefinition, $requestMethod, $url, $requestData, $ex));
            abort_with_data($ex->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR, $ex->getMessage());
        } catch (GuzzleException $ex) {
            Log::error('DCP client transport error.', $this->buildErrorContext($endpointDefinition, $requestMethod, $url, $requestData, $ex));
            abort_with_data(Response::HTTP_INTERNAL_SERVER_ERROR, 'Cannot connect to DCP server');
        } catch (Exception $ex) {
            Log::error('DCP client unexpected error.', $this->buildErrorContext($endpointDefinition, $requestMethod, $url, $requestData, $ex));
            abort_with_data(Response::HTTP_INTERNAL_SERVER_ERROR, $ex->getMessage());
        }
    }

    /**
     * Makes an async HTTP call to a configured endpoint, returning a promise.
     *
     * @param array $endpointDefinition Endpoint configuration.
     * @param array|null $data Data to send with the request.
     *
     * @return PromiseInterface A promise that resolves to parsed response data or rejects on error.
     */
    private function callAsync(array $endpointDefinition, ?array $data = null): PromiseInterface
    {
        $this->validateParams($endpointDefinition, $data);

        [$requestMethod, $url, $requestData] = $this->buildRequest($endpointDefinition, $data);

        $options = $this->buildRequestOptions($requestMethod, $requestData);
        $requestMethod = strtoupper($requestMethod);

        return $this->client->requestAsync($requestMethod, $url, $options)
            ->then(function ($response) use ($endpointDefinition) {
                $responseBody = (string) $response->getBody();
                return $this->parseResponse($endpointDefinition, $responseBody);
            })
            ->otherwise(function ($reason) use ($endpointDefinition, $requestMethod, $url, $requestData) {
                // Log the error and re-throw so caller can handle it via promise rejection
                if ($reason instanceof ClientException) {
                    Log::error('DCP client async request failed.', $this->buildErrorContext(
                        $endpointDefinition, $requestMethod, $url, $requestData, $reason
                    ));
                } elseif ($reason instanceof GuzzleException) {
                    Log::error('DCP client async transport error.', $this->buildErrorContext(
                        $endpointDefinition, $requestMethod, $url, $requestData, $reason
                    ));
                } else {
                    Log::error('DCP client async unexpected error.', $this->buildErrorContext(
                        $endpointDefinition, strtolower($requestMethod), $url, $requestData, $reason
                    ));
                }
                throw $reason;
            });
    }

    /**
     * Builds Guzzle request options (headers, query/json body) for the given method,
     * mirroring the get()/post()/delete() wrappers used by the synchronous path.
     *
     * @param string $requestMethod Lowercase HTTP method (get, post, delete).
     * @param array $requestData Tuple data from buildRequest() (query, additional_data).
     *
     * @return array Guzzle-compatible request options.
     */
    private function buildRequestOptions(string $requestMethod, array $requestData): array
    {
        $options = ['headers' => $this->generateHeaders()];

        if ($requestMethod === 'post') {
            $options['json'] = $requestData['additional_data'];
            $options['query'] = $requestData['query'];
        } else {
            $options['query'] = $requestData['additional_data'];
        }

        return $options;
    }

    /**
     * Builds request parameters (method, URL, and data) from endpoint definition.
     *
     * @param array $endpointDefinition Endpoint configuration.
     * @param array|null $data Request data provided.
     *
     * @return array Tuple of [requestMethod, url, requestData].
     */
    private function buildRequest(array $endpointDefinition, ?array $data = null): array
    {
        // Build param map from params definition (with correct casing)
        $paramMap = [];
        foreach ($endpointDefinition['params'] ?? [] as $param) {
            $isOptional = str_ends_with($param, '?');
            $paramName = $isOptional ? substr($param, 0, -1) : $param;

            // Match input data case-insensitively
            foreach ($data ?? [] as $key => $value) {
                if (strtolower($key) === strtolower($paramName)) {
                    $paramMap[$paramName] = $value; // Use param definition's casing
                    break;
                }
            }
        }

        $url = $endpointDefinition['url'];
        $requestMethod = strtolower($endpointDefinition['method']);

        // Extract which parameters are path parameters (in URL pattern)
        $pathParams = $this->extractPathParams($url);

        // Replace path parameters in URL (e.g., {SiteId} or :SiteId)
        $url = $this->replacePathParams($url, $paramMap, $pathParams);

        // Separate remaining params for query string
        $queryParams = array_diff_key($paramMap, array_flip($pathParams));

        $requestData = [
            'query'           => $queryParams,
            'additional_data' => $paramMap,
        ];

        return [$requestMethod, $url, $requestData];
    }

    /**
     * Parses response body according to endpoint definition.
     *
     * @param array $endpointDefinition Endpoint configuration.
     * @param string $responseBody Raw response body.
     *
     * @return mixed Parsed response data or null if empty.
     * @throws JsonException If JSON decoding fails.
     */
    private function parseResponse(array $endpointDefinition, string $responseBody): mixed
    {
        if (empty(trim($responseBody))) {
            return null;
        }

        if (!array_key_exists('type', $endpointDefinition)) {
            return Json::decode($responseBody);
        }

        return FormatsDcpClientResponse::applyDataClassOnResponse(
            Json::decodeAssoc($responseBody),
            $endpointDefinition['type'],
        );
    }

    /**
     * Builds a structured context array for exception logging.
     */
    private function buildErrorContext(array $endpointDefinition, string $requestMethod, string $url, array $requestData, Throwable $exception): array
    {
        $response = null;
        $responseBody = null;
        $statusCode = null;

        if ($exception instanceof ClientException) {
            $response = $exception->getResponse();
            $statusCode = $response?->getStatusCode();
            $responseBody = $response ? (string) $response->getBody() : null;
        }

        $context = [
            'endpoint' => $endpointDefinition['url'] ?? null,
            'method' => strtoupper($requestMethod),
            'url' => $url,
            'status_code' => $statusCode,
            'exception_class' => get_class($exception),
        ];

        if (!$this->shouldLogDetailedErrors()) {
            return $context;
        }

        return array_merge($context, [
            'request_data' => $this->sanitizeLoggingData($requestData),
            'response_body' => $this->sanitizeLoggingData($responseBody),
            'exception_message' => $exception->getMessage(),
        ]);
    }

    /**
     * Determines whether detailed request and response payloads should be logged.
     */
    private function shouldLogDetailedErrors(): bool
    {
        return app()->environment(['local', 'dev', 'development']) || app()->isLocal();
    }

    /**
     * Sanitizes request or response payloads before logging.
     */
    private function sanitizeLoggingData(mixed $value, ?string $key = null): mixed
    {
        if (is_array($value)) {
            $sanitized = [];

            foreach ($value as $itemKey => $itemValue) {
                $sanitized[$itemKey] = $this->sanitizeLoggingData($itemValue, (string) $itemKey);
            }

            return $sanitized;
        }

        if (is_string($value)) {
            if ($this->isSensitiveKey($key)) {
                return '[REDACTED]';
            }

            return mb_substr($value, 0, 2000);
        }

        if (is_object($value)) {
            return get_class($value);
        }

        return $value;
    }

    /**
     * Determines whether a payload field should be redacted from logs.
     */
    private function isSensitiveKey(?string $key): bool
    {
        return $key !== null && preg_match('/(token|secret|password|authorization|cookie|api[_-]?key)/i', $key) === 1;
    }

    /**
     * Validates that necessary parameters are provided for the request.
     *
     * @param array $endpointDefinition The endpoint's configuration.
     * @param array|null $data Request data provided.
     */
    private function validateParams(array $endpointDefinition, ?array $data): void
    {
        if (empty($endpointDefinition['params'])) {
            return;
        }

        $required = [];
        foreach ($endpointDefinition['params'] as $param) {
            $paramName = str_ends_with($param, '?') ? substr($param, 0, -1) : $param;
            if (!str_ends_with($param, '?')) {
                $required[] = $paramName;
            }
        }

        $providedParams = array_keys($data ?? []);
        $missing = array_diff($required, $providedParams);

        if (!empty($missing)) {
            abort(500, 'Missing required parameters: ' . implode(', ', $missing));
        }
    }

    /**
     * Extracts path parameter names from a URL pattern.
     *
     * @param string $url The URL pattern (e.g., 'core/v2/admin/site/{SiteId}/get')
     * @return array Array of parameter names found in the URL
     */
    private function extractPathParams(string $url): array
    {
        $matches = [];
        // Match both {SiteId} and :SiteId
        preg_match_all('/[:{](\w+)[}]?/', $url, $matches);
        return array_unique($matches[1] ?? []);
    }

    /**
     * Replaces placeholders in the URL path with provided data.
     *
     * @param string $url URL with placeholders for parameters.
     * @param array $data Data to replace placeholders.
     *
     * @return string The URL with replaced parameters.
     */
    private function replacePathParams(string $url, array $data, array $pathParams): string
    {
        foreach ($pathParams as $paramName) {
            if (!isset($data[$paramName])) {
                continue;
            }

            $value = $data[$paramName];

            // Skip complex types
            if (is_array($value) || is_object($value) || is_resource($value)) {
                continue;
            }

            $value = (string) $value;

            // Replace {SiteId} format
            $url = str_replace('{' . $paramName . '}', $value, $url);

            // Replace :SiteId format
            $url = str_replace(':' . $paramName, $value, $url);
        }

        return $url;
    }

    /**
     * Replaces placeholders in the URL with provided data.
     *
     * @param string $url URL with placeholders for parameters.
     * @param array $data Data to replace placeholders.
     *
     * @return string The URL with replaced parameters.
     */
    private function replaceUrlParams(string $url, array $data): string
    {
        if (!str_contains($url, ':')) {
            return $url;
        }

        foreach ($data as $key => $value) {
            if (!str_contains($url, ':' . $key)) {
                continue;
            }

            if (is_array($value)) {
                $value = implode('&' . $key . '=', $value);
            }

            if (is_array($value) || is_object($value) || is_resource($value)) {
                continue;
            }

            $url = str_replace(':' . $key, (string) $value, $url);
        }

        // Clean up unresolved placeholders and empty query parameters
        $parts = parse_url($url);
        $baseUrl = strtok($url, '?');
        $cleanQuery = '';

        if (isset($parts['query'])) {
            // Split query into parts
            $queryParts = explode('&', $parts['query']);
            $filtered = [];

            foreach ($queryParts as $part) {
                // Remove parameters with unresolved placeholders or empty values
                if (
                    str_contains($part, '=:') || // still has a placeholder
                    preg_match('/=\s*$/', $part) // empty value
                ) {
                    continue;
                }
                $filtered[] = $part;
            }

            if (!empty($filtered)) {
                $cleanQuery = implode('&', $filtered);
            }
        }

        return $cleanQuery ? ($baseUrl . '?' . $cleanQuery) : $baseUrl;
    }

    /**
     * Authenticates the client using server-to-server credentials.
     *
     * @throws GuzzleException If a request error occurs.
     * @throws JsonException If JSON decoding fails.
     */
    private function serverAuth(): void
    {
        // Check for a cached token and validate its expiration
        $serverToken = Storage::disk('local')->exists(DCP_TOKEN_JSON)
            ? Json::decode(Storage::disk('local')->get(DCP_TOKEN_JSON), false)
            : null;

        $isTokenValid = false;

        if ($serverToken && isset($serverToken->access_token)) {
            try {
                $jwt = JWT::decode(
                    $serverToken->access_token,
                    new Key(config('dcp.environment.jwt_token'), 'HS256')
                );

                // Check if token is still valid (with 5 second buffer)
                $isTokenValid = $jwt->exp > time() + 5;
            } catch (\Exception $e) {
                // Token is invalid or malformed
                $isTokenValid = false;
            }
        }

        if (!$isTokenValid) {
            // Request a new token from the server
            $serverToServer = $this->client->request('POST', '/core/v2/token', [
                'query' => [
                    'clientId'     => config('dcp.environment.client_id'),
                    'clientSecret' => config('dcp.environment.client_secret'),
                    'grant_type'   => 'client_credentials',
                ],
            ]);

            // Decode and cache the token
            $serverToken = Json::decode($serverToServer->getBody()) ?: null;
            if ($serverToken) {
                Storage::disk('local')->put(DCP_TOKEN_JSON, Json::encode($serverToken));
            }
        }

        $this->accessToken = $serverToken?->access_token ?? null;
    }

    /**
     * Authenticates the client using a user-provided access token.
     */
    private function userAuth(): void
    {
        // Get access token from request headers
        $authorizationHeader = request()->header('authorization');

        if (is_null($authorizationHeader)) {
            abort_with_data(
                Response::HTTP_BAD_REQUEST,
                'No auth header provided. Maybe need to use server-to-server auth?',
            );
        }

        // Remove "Bearer " prefix if present
        if (str_starts_with($authorizationHeader, TOKEN_STARTER)) {
            $authorizationHeader = substr($authorizationHeader, strlen(TOKEN_STARTER));
        }

        $this->accessToken = $authorizationHeader;
    }

    /**
     * Makes a GET request to the specified URL.
     *
     * @param string $url The request URL.
     * @param array|null $data Data to include in the URL as parameters.
     *
     * @return ResponseInterface The response from the server.
     * @throws GuzzleException If a request error occurs.
     */
    private function get($url, $data): ResponseInterface
    {
        return $this->client->request('GET', $url, [
            'headers' => $this->generateHeaders(),
            'query'   => $data['additional_data'], // Use mapped params
        ]);
    }

    /**
     * Makes a POST request to the specified URL.
     *
     * @param string $url The request URL.
     * @param array $data The data to send in the request body.
     *
     * @return ResponseInterface The response from the server.
     * @throws GuzzleException If a request error occurs.
     */
    private function post($url, $data): ResponseInterface
    {
        return $this->client->request('POST', $url, [
            'headers' => $this->generateHeaders(),
            'json'    => $data['additional_data'],
            'query'   => $data['query'],
        ]);
    }

    /**
     * Makes a DELETE request to the specified URL.
     *
     * @param string $url The request URL.
     * @param array|null $data The data to send as query parameters.
     *
     * @return ResponseInterface The response from the server.
     * @throws GuzzleException If a request error occurs.
     */
    private function delete($url, $data): ResponseInterface
    {
        return $this->client->request('DELETE', $url, [
            'headers' => $this->generateHeaders(),
            'query'   => $data['additional_data'],
        ]);
    }

    /**
     * Generates the headers required for authenticated requests.
     *
     * @return array Headers including authorization and custom keys.
     */
    private function generateHeaders(): array
    {
        return [
            'Authorization'         => TOKEN_STARTER . $this->accessToken,
            'X-DCPMicroservice-Key' => config('dcp.environment.core_key'),
        ];
    }
}
