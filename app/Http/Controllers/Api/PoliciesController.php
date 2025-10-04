<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\PolicyAcceptance;

class PoliciesController extends Controller
{
    /**
     * Current policy metadata (URLs and versions).
     * In production, fetch from DB/AdminSetting.
     */
    protected function versions(): array
    {
        return [
            'terms' => [
                'url' => url('/policies#terms'),
                'version' => '1.0.0',
            ],
            'privacy' => [
                'url' => url('/policies#privacy'),
                'version' => '1.0.0',
            ],
        ];
    }

    public function index(Request $request)
    {
        $data = $this->versions();
        // Backwards-compatible extra field
        $data['fees'] = [
            'url' => url('/policies#fees'),
            'version' => '1.0.0',
        ];
        return response()->json($data);
    }

    /**
     * Return whether the authenticated user has accepted latest versions.
     */
    public function status(Request $request)
    {
        $user = $request->user();
        $versions = $this->versions();
        $accepted = false;

        if ($user) {
            $last = PolicyAcceptance::query()
                ->where('user_id', $user->id)
                ->latest('accepted_at')
                ->first();
            if ($last) {
                $accepted = ($last->terms_version === ($versions['terms']['version'] ?? null))
                    && ($last->privacy_version === ($versions['privacy']['version'] ?? null));
            }
        }

        return response()->json([
            'accepted' => (bool) $accepted,
            'versions' => [
                'terms' => $versions['terms']['version'] ?? null,
                'privacy' => $versions['privacy']['version'] ?? null,
            ],
        ]);
    }

    /**
     * Record acceptance for the authenticated user.
     */
    public function accept(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $versions = $this->versions();
        $signature = (string) ($request->input('signature') ?? '');

        PolicyAcceptance::create([
            'user_id' => $user->id,
            'terms_version' => $versions['terms']['version'] ?? null,
            'privacy_version' => $versions['privacy']['version'] ?? null,
            'accepted_at' => now(),
            'signature' => $signature,
            'signature_hash' => $signature !== '' ? hash('sha256', $signature) : null,
            'ip' => $request->ip(),
            'user_agent' => (string) $request->header('User-Agent'),
        ]);

        return response()->json(['success' => true]);
    }
}
