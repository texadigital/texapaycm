<?php

namespace App\Http\Controllers\Kyc;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class KycController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        return view('kyc.index', [
            'user' => $user,
        ]);
    }

    public function status(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'kyc_status' => (string) ($user->kyc_status ?? 'unverified'),
            'kyc_level' => (int) ($user->kyc_level ?? 0),
            'kyc_verified_at' => $user->kyc_verified_at?->toISOString(),
        ]);
    }
}
