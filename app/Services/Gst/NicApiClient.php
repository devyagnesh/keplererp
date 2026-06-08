<?php

namespace App\Services\Gst;

use App\Exceptions\NicApiException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * HTTP client for NIC / GSP e-Invoice and e-Way REST APIs with token caching.
 */
class NicApiClient
{
    /**
     * @param  'einvoice'|'eway'  $module
     */
    public function authenticate(string $module): string
    {
        $config = config("{$module}.nic");
        $cacheKey = "nic.auth.{$module}.".($config['gstin'] ?? 'default');

        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $baseUrl = rtrim((string) ($config['base_url'] ?? ''), '/');
        if ($baseUrl === '') {
            throw new NicApiException("{$module}.nic.base_url is not configured.");
        }

        $authPath = (string) config("{$module}.nic.auth_path", '/authenticate');
        $body = [
            'username' => $config['username'] ?? '',
            'password' => $config['password'] ?? '',
            'client_id' => $config['client_id'] ?? '',
            'client_secret' => $config['client_secret'] ?? '',
            'gstin' => $config['gstin'] ?? '',
        ];

        $response = $this->http($module)->post($baseUrl.$authPath, $body);
        $this->assertSuccess($response, 'NIC authentication failed');

        $json = $response->json();
        if (! is_array($json)) {
            throw new NicApiException('NIC authentication returned invalid JSON.');
        }

        $token = $this->extractToken($json);
        if ($token === '') {
            throw new NicApiException('NIC authentication response did not include an access token.', $response->status(), ['body' => $json]);
        }

        $ttl = (int) config("{$module}.nic.token_ttl_seconds", 3300);
        Cache::put($cacheKey, $token, max(60, $ttl));

        return $token;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function postAuthenticated(string $module, string $path, array $payload): array
    {
        $baseUrl = rtrim((string) config("{$module}.nic.base_url", ''), '/');
        if ($baseUrl === '') {
            throw new NicApiException("{$module}.nic.base_url is not configured.");
        }

        $token = $this->authenticate($module);
        $response = $this->http($module)
            ->withToken($token)
            ->post($baseUrl.$path, $payload);

        $this->assertSuccess($response, "NIC {$module} API request failed");

        $json = $response->json();
        if (! is_array($json)) {
            throw new NicApiException("NIC {$module} API returned invalid JSON.", $response->status());
        }

        return $json;
    }

    /**
     * @param  array<string, mixed>  $response
     */
    public function extractToken(array $response): string
    {
        foreach (['access_token', 'token', 'auth_token', 'AuthToken', 'authtoken'] as $key) {
            if (! empty($response[$key]) && is_string($response[$key])) {
                return $response[$key];
            }
        }

        $data = $response['data'] ?? $response['Data'] ?? null;
        if (is_array($data)) {
            return $this->extractToken($data);
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $response
     */
    public function extractScalar(array $response, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($response[$key]) && is_string($response[$key]) && $response[$key] !== '') {
                return $response[$key];
            }
        }

        foreach (['data', 'Data', 'result', 'Result'] as $wrapper) {
            if (isset($response[$wrapper]) && is_array($response[$wrapper])) {
                $found = $this->extractScalar($response[$wrapper], $keys);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    public function isConfigured(string $module): bool
    {
        $nic = config("{$module}.nic", []);

        return ($nic['base_url'] ?? '') !== ''
            && ($nic['username'] ?? '') !== ''
            && ($nic['password'] ?? '') !== ''
            && ($nic['gstin'] ?? '') !== '';
    }

    /**
     * @param  'einvoice'|'eway'  $module
     */
    protected function http(string $module): PendingRequest
    {
        $timeout = (int) config("{$module}.nic.timeout_seconds", 30);

        return Http::timeout($timeout)
            ->acceptJson()
            ->asJson();
    }

    protected function assertSuccess(Response $response, string $message): void
    {
        if ($response->successful()) {
            return;
        }

        $body = $response->json();
        $detail = is_array($body)
            ? ($body['message'] ?? $body['error'] ?? $body['ErrorMessage'] ?? json_encode($body))
            : $response->body();

        Log::warning($message, [
            'status' => $response->status(),
            'body' => $body,
        ]);

        throw new NicApiException(
            is_string($detail) ? $detail : $message,
            $response->status(),
            ['body' => $body]
        );
    }
}
