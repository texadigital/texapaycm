<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SafeHaven
{
    protected string $baseUrl;
    protected ?string $apiKey;
    protected ?string $clientId;
    protected ?string $clientAssertion;
    protected ?string $ibsClientId;
    protected ?string $accessToken = null;
    protected ?string $issuer;
    protected ?string $audience;
    protected ?string $scopes;
    protected ?string $privateKeyPath;
    protected ?string $privateKeyPassphrase;
    protected ?string $keyId;
    protected ?array $lastAuthError = null;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) env('SAFEHAVEN_BASE_URL', ''), '/');
        $this->apiKey = env('SAFEHAVEN_API_KEY'); // optional legacy
        $this->clientId = env('SAFEHAVEN_CLIENT_ID');
        $this->clientAssertion = env('SAFEHAVEN_CLIENT_ASSERTION');
        $this->ibsClientId = env('SAFEHAVEN_IBS_CLIENT_ID');
        $this->issuer = env('SAFEHAVEN_ISSUER');
        $this->audience = env('SAFEHAVEN_OAUTH_AUD');
        $this->scopes = env('SAFEHAVEN_SCOPES');
        $this->privateKeyPath = env('SAFEHAVEN_PRIVATE_KEY_PATH');
        $this->privateKeyPassphrase = env('SAFEHAVEN_PRIVATE_KEY_PASSPHRASE');
        $this->keyId = env('SAFEHAVEN_KEY_ID');
    }

    /**
     * Fetch list of Nigerian banks from Safe Haven.
     */
    public function listBanks(): array
    {
        if (!$this->baseUrl) {
            return [
                'status' => 'failed',
                'raw' => ['error' => 'Missing SAFEHAVEN_BASE_URL or credentials'],
            ];
        }
        try {
            $resp = $this->client()->get($this->baseUrl . '/transfers/banks');
            return $resp->json();
        } catch (\Throwable $e) {
            return [
                'status' => 'failed',
                'raw' => ['exception' => $e->getMessage()],
            ];
        }
    }

    protected function client()
    {
        $token = $this->getAccessToken();
        $headers = [
            'Content-Type' => 'application/json',
        ];
        if ($token) {
            $headers['Authorization'] = 'Bearer ' . $token;
        } elseif ($this->apiKey) {
            $headers['Authorization'] = 'Bearer ' . $this->apiKey;
        }
        // Prefer IBS client id from token; fall back to OAuth client id if missing (as used in prior integration)
        $clientHeader = $this->ibsClientId ?: $this->clientId;
        if (!empty($clientHeader)) {
            $headers['ClientID'] = $clientHeader;
        }
        $http = Http::acceptJson();
        // Prefer a custom CA bundle if provided (Windows may need this explicitly)
        $caBundle = env('SAFEHAVEN_CA_BUNDLE');
        if (!empty($caBundle)) {
            $candidate = $caBundle;
            $isAbsolute = str_starts_with($candidate, DIRECTORY_SEPARATOR)
                || (strlen($candidate) > 1 && ctype_alpha($candidate[0]) && $candidate[1] === ':')
                || str_starts_with($candidate, 'phar://');
            if (!$isAbsolute && function_exists('base_path')) {
                $candidate = base_path($candidate);
            }
            $real = realpath($candidate) ?: $candidate;
            if (is_readable($real)) {
                $http = $http->withOptions(['verify' => $real]);
            }
        }
        return $http->withHeaders($headers);
    }

    /**
     * Obtain OAuth2 access token using client_credentials + client_assertion.
     */
    protected function getAccessToken(): ?string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }
        if (!$this->baseUrl || !$this->clientId) {
            return null;
        }
        // Generate client assertion JWT at runtime if not provided in env
        $assertion = $this->clientAssertion ?: $this->generateClientAssertion();
        if (!$assertion) {
            $this->lastAuthError = ['stage' => 'assertion', 'error' => 'Failed to build client assertion (check key path/permissions).'];
            return null;
        }
        try {
            $form = [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_assertion' => $assertion,
                'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            ];
            if (!empty($this->scopes)) {
                $form['scope'] = $this->scopes;
            }

            // Build HTTP client for token request, honoring custom CA bundle
            $http = Http::asForm();
            $caBundle = env('SAFEHAVEN_CA_BUNDLE');
            if (!empty($caBundle)) {
                $candidate = $caBundle;
                $isAbsolute = str_starts_with($candidate, DIRECTORY_SEPARATOR)
                    || (strlen($candidate) > 1 && ctype_alpha($candidate[0]) && $candidate[1] === ':')
                    || str_starts_with($candidate, 'phar://');
                if (!$isAbsolute && function_exists('base_path')) {
                    $candidate = base_path($candidate);
                }
                $real = realpath($candidate) ?: $candidate;
                if (is_readable($real)) {
                    $http = $http->withOptions(['verify' => $real]);
                }
            }
            $resp = $http->post($this->baseUrl . '/oauth2/token', $form);
            if (!$resp->successful()) {
                // Retry once with alternate audience (some tenants expect base URL as aud)
                if (empty($this->clientAssertion) && $this->audience && str_contains($this->audience, '/oauth2/token')) {
                    $altAud = rtrim(str_replace('/oauth2/token', '', $this->audience), '/');
                    $assertion2 = $this->generateClientAssertionWithAudience($altAud);
                    if ($assertion2) {
                        $form['client_assertion'] = $assertion2;
                        $resp = $http->post($this->baseUrl . '/oauth2/token', $form);
                    }
                }
            }
            if (!$resp->successful()) {
                $this->lastAuthError = [
                    'stage' => 'token_request',
                    'status' => $resp->status(),
                    'body' => $resp->json() ?? $resp->body(),
                ];
                return null;
            }
            $json = $resp->json();
            $this->accessToken = $json['access_token'] ?? null;
            // Some responses may include ibs_client_id; set header for subsequent calls
            if (isset($json['ibs_client_id']) && !$this->ibsClientId) {
                $this->ibsClientId = $json['ibs_client_id'];
            }
            if (empty($this->accessToken)) {
                $this->lastAuthError = [
                    'stage' => 'token_json',
                    'error' => 'No access_token in response',
                    'json_keys' => array_keys((array) $json),
                ];
                return null;
            }
            $this->lastAuthError = null;
            return $this->accessToken;
        } catch (\Throwable $e) {
            $this->lastAuthError = ['stage' => 'exception', 'error' => $e->getMessage()];
            return null;
        }
    }

    /**
     * Build a client assertion JWT (RS256) from env values and private key.
     */
    protected function generateClientAssertion(): ?string
    {
        try {
            if (!$this->clientId) {
                $this->lastAuthError = ['stage' => 'assertion', 'error' => 'Missing clientId'];
                return null;
            }
            $iss = $this->issuer ?: $this->clientId;
            $sub = $this->clientId;
            $aud = $this->audience ?: ($this->baseUrl . '/oauth2/token');
            $iat = time();
            $exp = $iat + 300; // 5 minutes
            $jti = bin2hex(random_bytes(16));

            $header = [
                'alg' => 'RS256',
                'typ' => 'JWT',
            ];
            if (!empty($this->keyId)) {
                $header['kid'] = $this->keyId;
            }
            $claims = [
                'iss' => $iss,
                'sub' => $sub,
                'aud' => $aud,
                'iat' => $iat,
                'exp' => $exp,
                'jti' => $jti,
            ];
            if (!empty($this->scopes)) {
                $claims['scope'] = $this->scopes;
            }

            $segments = [
                $this->base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES)),
                $this->base64UrlEncode(json_encode($claims, JSON_UNESCAPED_SLASHES)),
            ];
            $signingInput = implode('.', $segments);

            // Cross-platform private key resolution
            $privateKey = $this->loadPrivateKey();
            if ($privateKey === null) {
                // $this->lastAuthError is already set inside loadPrivateKey()
                return null;
            }
            $pkey = openssl_pkey_get_private($privateKey, $this->privateKeyPassphrase ?: '');
            if ($pkey === false) {
                $this->lastAuthError = ['stage' => 'assertion', 'error' => 'Failed to load private key (bad format or wrong passphrase?)'];
                return null;
            }
            $signature = '';
            $ok = openssl_sign($signingInput, $signature, $pkey, OPENSSL_ALGO_SHA256);
            openssl_pkey_free($pkey);
            if (!$ok) {
                $this->lastAuthError = ['stage' => 'assertion', 'error' => 'OpenSSL signing failed'];
                return null;
            }
            $segments[] = $this->base64UrlEncode($signature);
            return implode('.', $segments);
        } catch (\Throwable $e) {
            $this->lastAuthError = ['stage' => 'assertion', 'error' => $e->getMessage()];
            return null;
        }
    }

    /**
     * Generate assertion with explicit audience override.
     */
    protected function generateClientAssertionWithAudience(string $aud): ?string
    {
        try {
            if (!$this->clientId) {
                $this->lastAuthError = ['stage' => 'assertion_alt_aud', 'error' => 'Missing clientId'];
                return null;
            }
            $iss = $this->issuer ?: $this->clientId;
            $sub = $this->clientId;
            $iat = time();
            $exp = $iat + 300;
            $jti = bin2hex(random_bytes(16));

            $header = [
                'alg' => 'RS256',
                'typ' => 'JWT',
            ];
            if (!empty($this->keyId)) {
                $header['kid'] = $this->keyId;
            }
            $claims = [
                'iss' => $iss,
                'sub' => $sub,
                'aud' => $aud,
                'iat' => $iat,
                'exp' => $exp,
                'jti' => $jti,
            ];
            if (!empty($this->scopes)) {
                $claims['scope'] = $this->scopes;
            }

            $segments = [
                $this->base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES)),
                $this->base64UrlEncode(json_encode($claims, JSON_UNESCAPED_SLASHES)),
            ];
            $signingInput = implode('.', $segments);

            $privateKey = $this->loadPrivateKey();
            if ($privateKey === null) {
                return null;
            }
            $pkey = openssl_pkey_get_private($privateKey, $this->privateKeyPassphrase ?: '');
            if ($pkey === false) {
                return null;
            }
            $signature = '';
            $ok = openssl_sign($signingInput, $signature, $pkey, OPENSSL_ALGO_SHA256);
            openssl_pkey_free($pkey);
            if (!$ok) {
                return null;
            }
            $segments[] = $this->base64UrlEncode($signature);
            return implode('.', $segments);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Resolve private key from env in a cross-platform way.
     * Priority:
     * 1) SAFEHAVEN_PRIVATE_KEY (inline PEM, supports \n)
     * 2) SAFEHAVEN_PRIVATE_KEY_BASE64 (base64-encoded PEM)
     * 3) SAFEHAVEN_PRIVATE_KEY_PATH (absolute or relative to base_path())
     */
    protected function loadPrivateKey(): ?string
    {
        // 1) Inline PEM
        $inline = env('SAFEHAVEN_PRIVATE_KEY');
        if (!empty($inline)) {
            $pem = (string) $inline;
            // Convert escaped newlines if present
            $pem = str_replace(["\\n", "\r\n"], "\n", $pem);
            return trim($pem);
        }

        // 2) Base64
        $b64 = env('SAFEHAVEN_PRIVATE_KEY_BASE64');
        if (!empty($b64)) {
            $decoded = base64_decode((string) $b64, true);
            if ($decoded !== false && trim($decoded) !== '') {
                return $decoded;
            }
            $this->lastAuthError = ['stage' => 'assertion', 'error' => 'Invalid SAFEHAVEN_PRIVATE_KEY_BASE64'];
            return null;
        }

        // 3) File path
        $path = $this->privateKeyPath;
        if (empty($path)) {
            $this->lastAuthError = ['stage' => 'assertion', 'error' => 'Private key not provided (set SAFEHAVEN_PRIVATE_KEY, SAFEHAVEN_PRIVATE_KEY_BASE64, or SAFEHAVEN_PRIVATE_KEY_PATH)'];
            return null;
        }

        $candidate = $path;
        $isAbsolute = str_starts_with($path, DIRECTORY_SEPARATOR)
            || (strlen($path) > 1 && ctype_alpha($path[0]) && $path[1] === ':')
            || str_starts_with($path, 'phar://');
        
        if (!$isAbsolute) {
            // Try Laravel's base_path() first
            if (function_exists('base_path')) {
                try {
                    $candidate = base_path($path);
                } catch (\Exception $e) {
                    // Fallback to current directory if base_path() fails
                    $candidate = getcwd() . DIRECTORY_SEPARATOR . $path;
                }
            } else {
                // Fallback to current directory
                $candidate = getcwd() . DIRECTORY_SEPARATOR . $path;
            }
        }
        if (!is_readable($candidate)) {
            $this->lastAuthError = ['stage' => 'assertion', 'error' => 'Private key path missing or unreadable', 'path' => $candidate];
            return null;
        }
        $contents = @file_get_contents($candidate);
        if ($contents === false) {
            $this->lastAuthError = ['stage' => 'assertion', 'error' => 'Failed to read private key file', 'path' => $candidate];
            return null;
        }
        return $contents;
    }

    /**
     * Public health check: verifies we can obtain an access token and whether IBS ClientID is set.
     */
    public function checkAuth(): array
    {
        $token = $this->getAccessToken();
        $keyPath = $this->privateKeyPath;
        $keyReadable = $keyPath ? is_readable($keyPath) : false;
        $clientHeader = $this->ibsClientId ?: $this->clientId;
        return [
            'ok' => (bool) $token,
            'access_token_present' => (bool) $token,
            'ibs_client_id' => $this->ibsClientId,
            'base_url' => $this->baseUrl,
            'client_id' => $this->clientId,
            'uses_runtime_assertion' => empty($this->clientAssertion),
            'auth_error' => $this->lastAuthError,
            'audience' => $this->audience ?: ($this->baseUrl ? $this->baseUrl . '/oauth2/token' : null),
            'scopes' => $this->scopes,
            'key_path' => $keyPath,
            'key_readable' => $keyReadable,
            'client_header_used' => $clientHeader,
        ];
    }

    /**
     * Name Enquiry: verify bank account name given bank code and account number.
     * Returns: ['success' => bool, 'account_name' => string|null, 'bank_name' => string|null, 'reference' => string|null, 'raw' => array]
     */
    public function nameEnquiry(string $bankCode, string $accountNumber): array
    {
        if (!$this->baseUrl) {
            return [
                'success' => false,
                'account_name' => null,
                'bank_name' => null,
                'reference' => null,
                'raw' => ['error' => 'Missing SAFEHAVEN_BASE_URL or credentials'],
            ];
        }

        try {
            // Real endpoint per docs: POST /transfers/name-enquiry
            $resp = $this->client()->post($this->baseUrl . '/transfers/name-enquiry', [
                'bankCode' => $bankCode,
                'accountNumber' => $accountNumber,
            ]);

            $json = $resp->json();
            $statusCode = $json['statusCode'] ?? $json['status'] ?? null;
            $respCode = $json['responseCode'] ?? ($json['data']['responseCode'] ?? null);
            $ok = $resp->ok() && (
                $statusCode === 200 || $respCode === '00' || ($json['success'] ?? false)
            );

            // Map known error codes to friendly messages
            if (!$ok && ($json['responseCode'] ?? null) === '63') {
                $json['friendlyMessage'] = 'Security restriction from provider in sandbox. Try SAFE HAVEN SANDBOX BANK (999240) for test accounts.';
            }

            return [
                'success' => $ok,
                'account_name' => $json['data']['accountName'] ?? ($json['accountName'] ?? null),
                'bank_name' => $json['data']['bankName'] ?? ($json['bankName'] ?? null),
                'reference' => $json['data']['sessionId'] ?? ($json['sessionId'] ?? null),
                'raw' => $json,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'account_name' => null,
                'bank_name' => null,
                'reference' => null,
                'raw' => ['exception' => $e->getMessage()],
            ];
        }
    }

    /**
     * Initiate NGN payout.
     * Input: ['amount_ngn_minor','bank_code','account_number','account_name','narration','reference','name_enquiry_reference','debit_account_number']
     * Returns: ['ref' => string|null, 'status' => 'pending|success|failed', 'raw' => array]
     */
    public function payout(array $payload): array
    {
        if (!$this->baseUrl) {
            return [
                'ref' => null,
                'status' => 'failed',
                'raw' => ['error' => 'Missing SAFEHAVEN_BASE_URL or credentials'],
            ];
        }

        try {
            // Real endpoint per docs: POST /transfers (requires nameEnquiryReference)
            $resp = $this->client()->post($this->baseUrl . '/transfers', [
                'nameEnquiryReference' => $payload['name_enquiry_reference'] ?? null,
                'debitAccountNumber' => $payload['debit_account_number'] ?? null,
                'beneficiaryBankCode' => $payload['bank_code'] ?? null,
                'beneficiaryAccountNumber' => $payload['account_number'] ?? null,
                'amount' => ($payload['amount_ngn_minor'] ?? 0) / 100,
                'saveBeneficiary' => false,
                'narration' => $payload['narration'] ?? null,
                'paymentReference' => $payload['reference'] ?? null,
            ]);

            $json = $resp->json();
            $httpOk = $resp->ok();
            $respCode = $json['responseCode'] ?? ($json['data']['responseCode'] ?? null);
            $dataStatus = strtolower((string)($json['data']['status'] ?? ''));
            $session = $json['data']['sessionId'] ?? ($json['sessionId'] ?? ($payload['reference'] ?? null));

            // Determine status robustly from body regardless of HTTP status
            $status = 'failed';
            if ($respCode === '00' || in_array($dataStatus, ['success', 'completed', 'approved'], true)) {
                $status = 'success';
            } elseif ($httpOk || ($json['status'] ?? null) == 200) {
                $status = 'pending';
            }

            return [
                'ref' => $session,
                'status' => $status,
                'raw' => $json,
            ];
        } catch (\Throwable $e) {
            return [
                'ref' => null,
                'status' => 'failed',
                'raw' => ['exception' => $e->getMessage()],
            ];
        }
    }

    /**
     * Check payout status using Safe Haven /transfers/status
     * Returns: ['status' => 'pending|success|failed', 'raw' => array]
     */
    public function payoutStatus(string $sessionId): array
    {
        if (!$this->baseUrl) {
            return [
                'status' => 'failed',
                'raw' => ['error' => 'Missing SAFEHAVEN_BASE_URL or credentials'],
            ];
        }

        try {
            $resp = $this->client()->post($this->baseUrl . '/transfers/status', [
                'sessionId' => $sessionId,
            ]);
            $json = $resp->json();
            $status = 'pending';
            if (($json['status'] ?? null) == 200) {
                $code = strtolower((string)($json['data']['status'] ?? ''));
                if (in_array($code, ['success', 'completed', 'approved'])) {
                    $status = 'success';
                } elseif (in_array($code, ['failed', 'rejected'])) {
                    $status = 'failed';
                }
            }
            return [
                'status' => $status,
                'raw' => $json,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'failed',
                'raw' => ['exception' => $e->getMessage()],
            ];
        }
    }
}
