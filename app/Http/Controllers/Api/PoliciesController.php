<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\PolicyAcceptance;

class PoliciesController extends Controller
{
    public function index(Request $request)
    {
        // Static placeholders; swap to AdminSetting or DB if available
        return response()->json([
            'terms' => [
                'url' => url('/policies#terms'),
                'version' => '1.0.0',
            ],
            'privacy' => [
                'url' => url('/policies#privacy'),
                'version' => '1.0.0',
            ],
            'fees' => [
                'url' => url('/policies#fees'),
                'version' => '1.0.0',
            ],
        ]);
    }

    /**
     * GET /api/mobile/policies/status
     * Returns whether current user has accepted current policy versions.
     */
    public function status(Request $request)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['accepted' => false], 401);

        $index = $this->index($request)->getData(true);
        $termsV = (string)($index['terms']['version'] ?? '1.0.0');
        $privacyV = (string)($index['privacy']['version'] ?? '1.0.0');

        $accepted = PolicyAcceptance::where('user_id', $user->id)
            ->where('terms_version', $termsV)
            ->where('privacy_version', $privacyV)
            ->exists();

        return response()->json([
            'accepted' => (bool)$accepted,
            'versions' => [ 'terms' => $termsV, 'privacy' => $privacyV ],
        ]);
    }

    /**
     * POST /api/mobile/policies/accept
     * Body: { signature?: string }
     */
    public function accept(Request $request)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);

        $data = $request->validate([
            'signature' => ['nullable','string','max:190'],
        ]);

        $index = $this->index($request)->getData(true);
        $termsV = (string)($index['terms']['version'] ?? '1.0.0');
        $privacyV = (string)($index['privacy']['version'] ?? '1.0.0');

        try {
            PolicyAcceptance::create([
                'user_id' => $user->id,
                'terms_version' => $termsV,
                'privacy_version' => $privacyV,
                'accepted_at' => now(),
                'signature' => $data['signature'] ?? null,
                'signature_hash' => isset($data['signature']) ? sha1($data['signature'] . '|' . $user->id) : null,
                'ip' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            Log::error('Policy accept failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Unable to record acceptance'], 500);
        }
    }
}
