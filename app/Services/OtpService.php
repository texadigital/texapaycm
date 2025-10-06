<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OtpService
{
    const PURPOSE_SIGNUP = 'signup';
    const PURPOSE_RESET  = 'reset';
    const PURPOSE_STEPUP = 'stepup';

    public function normalizeIdentifier(string $id): string
    {
        $id = trim($id);
        // Simple MSISDN normalization: keep digits and '+'; ensure leading '+' if it looks like a phone
        $digits = preg_replace('/[^0-9+]/', '', $id);
        if ($digits && $digits[0] !== '+') {
            // If it's e.g. 237XXXXXXXX, accept as-is; otherwise, prefix '+'
            if (preg_match('/^\d{9,15}$/', $digits)) {
                $digits = '+' . $digits;
            }
        }
        return strtolower($digits ?: $id);
    }

    public function generateCode(int $len = 6): string
    {
        $min = (int) (10 ** ($len - 1));
        $max = (int) (10 ** $len - 1);
        return (string) random_int($min, $max);
    }

    protected function hashCode(string $code): string
    {
        // Hash with HMAC using APP_KEY as pepper
        $pepper = config('app.key');
        return hash_hmac('sha256', $code, (string) $pepper);
    }

    public function create(string $purpose, string $identifier, int $ttlSeconds = 300): array
    {
        $key = $this->normalizeIdentifier($identifier);
        $code = $this->generateCode(6);
        $hash = $this->hashCode($code);
        $now = now();
        $exp = CarbonImmutable::now()->addSeconds($ttlSeconds);

        DB::table('otp_codes')->insert([
            'purpose'    => $purpose,
            'key'        => $key,
            'code_hash'  => $hash,
            'attempts'   => 0,
            'expires_at' => $exp,
            'created_at' => $now,
            'updated_at' => $now,
            'ip'         => request()->ip(),
            'ua'         => substr((string) request()->header('User-Agent'), 0, 255),
        ]);

        return ['key' => $key, 'code' => $code, 'expires_at' => $exp->toIso8601String()];
    }

    public function verify(string $purpose, string $identifier, string $code, int $maxAttempts = 5): bool
    {
        $key = $this->normalizeIdentifier($identifier);
        $hash = $this->hashCode($code);
        $row = DB::table('otp_codes')
            ->where('purpose', $purpose)
            ->where('key', $key)
            ->whereNull('used_at')
            ->orderByDesc('id')
            ->first();
        if (!$row) return false;
        if (now()->greaterThan($row->expires_at)) return false;
        if ($row->attempts >= $maxAttempts) return false;
        $ok = hash_equals($row->code_hash, $hash);
        DB::table('otp_codes')->where('id', $row->id)->update([
            'attempts' => $row->attempts + 1,
            'updated_at' => now(),
            'used_at' => $ok ? now() : $row->used_at,
        ]);
        return $ok;
    }
}
