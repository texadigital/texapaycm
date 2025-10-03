<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $u = $request->user();
        return response()->json([
            'id' => $u->id,
            'name' => $u->name,
            'phone' => $u->phone,
            'email' => $u->email,
            'kyc' => [
                'status' => $u->kyc_status ?? 'unverified',
                'level' => (int) ($u->kyc_level ?? 0),
                'verifiedAt' => $u->kyc_verified_at?->toISOString(),
            ],
        ]);
    }

    public function notifications(Request $request)
    {
        $u = $request->user();
        $key = 'user:notify:' . $u->id;
        $prefs = Cache::get($key, [
            'email' => ['transfers' => true, 'promos' => false],
            'sms' => ['alerts' => true],
            'push' => ['alerts' => true],
        ]);
        return response()->json($prefs);
    }

    public function updateNotifications(Request $request)
    {
        $u = $request->user();
        $data = $request->validate([
            'email' => ['nullable','array'],
            'sms' => ['nullable','array'],
            'push' => ['nullable','array'],
        ]);
        $key = 'user:notify:' . $u->id;
        $existing = Cache::get($key, []);
        $merged = array_merge($existing, $data);
        Cache::put($key, $merged, now()->addYear());
        return response()->json(['success' => true, 'preferences' => $merged]);
    }

    // Return editable personal info fields (parity with web personal-info)
    public function personalInfo(Request $request)
    {
        $u = $request->user();
        return response()->json([
            'name' => $u->name,
            'email' => $u->email,
            'phone' => $u->phone,
            'notification_email' => $u->notification_email,
        ]);
    }

    // Update personal info
    public function updatePersonalInfo(Request $request)
    {
        $data = $request->validate([
            'name' => ['nullable','string','max:255'],
            'email' => ['nullable','email','max:255'],
            'notification_email' => ['nullable','email','max:255'],
        ]);
        $u = $request->user();
        foreach ($data as $k => $v) {
            $u->{$k} = $v;
        }
        $u->save();
        return response()->json(['success' => true, 'user' => [
            'name' => $u->name,
            'email' => $u->email,
            'phone' => $u->phone,
            'notification_email' => $u->notification_email,
        ]]);
    }

    // Account deletion (soft delete)
    public function deleteAccount(Request $request)
    {
        $u = $request->user();
        // Optional confirmation token/code can be validated here
        $u->delete();
        return response()->json(['success' => true, 'message' => 'Account scheduled for deletion.']);
    }
}
