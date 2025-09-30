<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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
}
