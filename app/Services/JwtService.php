<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;

class JwtService
{
    private string $algo;
    private string $secret;

    public function __construct()
    {
        $this->algo = env('JWT_ALGO', 'HS256');
        // Use a stable secret across requests. If JWT_SECRET is not set, fall back to APP_KEY.
        // Generating a random secret per request causes issued tokens to fail verification on the next request.
        $envSecret = env('JWT_SECRET');
        $this->secret = $envSecret ?: (string) config('app.key');
    }

    public function makeAccessToken(User $user, array $extra = [], int $ttlSeconds = 900): string
    {
        $now = time();
        $payload = array_merge([
            'iss' => config('app.url'),
            'aud' => config('app.url'),
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $ttlSeconds,
            'sub' => (string) $user->id,
            'typ' => 'access',
        ], $extra);

        return JWT::encode($payload, $this->secret, $this->algo);
    }

    public function parse(string $jwt): object
    {
        return JWT::decode($jwt, new Key($this->secret, $this->algo));
    }
}
