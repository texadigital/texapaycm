<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserSecuritySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SecurityController extends Controller
{
    public function show(Request $request)
    {
        $u = $request->user();
        $sec = UserSecuritySetting::firstOrCreate(['user_id' => $u->id]);
        return response()->json([
            'pinEnabled' => (bool) $sec->pin_enabled,
            'twoFactorEnabled' => (bool) $sec->two_factor_enabled,
            'lastSecurityUpdate' => $sec->last_security_update?->toISOString(),
        ]);
    }

    public function updatePin(Request $request)
    {
        $data = $request->validate([
            'currentPin' => ['nullable','string','min:4','max:6'],
            'newPin' => ['required','string','regex:/^\d{4,6}$/'],
        ]);
        $u = $request->user();
        $sec = UserSecuritySetting::firstOrCreate(['user_id' => $u->id]);
        if ($sec->pin_enabled && $sec->pin_hash) {
            if (empty($data['currentPin']) || !Hash::check($data['currentPin'], $sec->pin_hash)) {
                return response()->json(['success' => false, 'code' => 'INVALID_PIN', 'message' => 'Current PIN is incorrect.'], 400);
            }
        }
        $sec->pin_hash = Hash::make($data['newPin']);
        $sec->pin_enabled = true;
        $sec->last_security_update = now();
        $sec->save();
        return response()->json(['success' => true]);
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'currentPassword' => ['required','string','min:6'],
            'newPassword' => ['required','string','min:8'],
        ]);
        $u = $request->user();
        if (!Hash::check($data['currentPassword'], $u->password)) {
            return response()->json(['success' => false, 'code' => 'INVALID_PASSWORD', 'message' => 'Current password is incorrect.'], 400);
        }
        $u->password = Hash::make($data['newPassword']);
        $u->save();
        return response()->json(['success' => true]);
    }
}
