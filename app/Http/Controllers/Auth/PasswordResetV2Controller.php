<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PasswordResetV2Controller extends Controller
{
    // Neutral initiation: never reveal if account exists
    public function forgot(Request $request, OtpService $otp, SmsService $sms)
    {
        $data = $request->validate([
            'identifier' => ['required','string','min:3','max:191'], // phone or email
        ]);
        $id = $otp->normalizeIdentifier($data['identifier']);
        $digits = preg_replace('/\D+/', '', $id);

        // Attempt to find a matching user by phone (either E.164 or digits-only) or email;
        // still respond neutrally even if not found
        $user = User::query()
            ->where(function($q) use ($id, $digits) {
                $q->where('phone', $id)
                  ->orWhere('phone', $digits);
            })
            ->orWhere('email', strtolower($id))
            ->first();

        if ($user) {
            // Create OTP and send
            $res = $otp->create(OtpService::PURPOSE_RESET, $user->phone ?: ($user->email ?? $id), 600);
            if ($user->phone) {
                $sms->send($user->phone, "Your TexaPay reset code is {$res['code']}. It expires in 10 minutes.");
            } elseif ($user->email) {
                try {
                    Mail::raw("Your TexaPay reset code is {$res['code']} (expires in 10 minutes).", function($m) use ($user) {
                        $m->to($user->email)->subject('TexaPay Password Reset Code');
                    });
                } catch (\Throwable $e) { Log::warning('Email send failed: '.$e->getMessage()); }
            }
        }

        return response()->json(['success' => true, 'message' => 'If the account exists, instructions have been sent.']);
    }

    public function reset(Request $request, OtpService $otp)
    {
        $data = $request->validate([
            'identifier' => ['required','string','min:3','max:191'],
            'code' => ['required','string','min:4','max:8'],
            'new_password' => ['required','string','min:8','max:128'],
        ]);
        $id = $otp->normalizeIdentifier($data['identifier']);
        $ok = $otp->verify(OtpService::PURPOSE_RESET, $id, $data['code']);
        if (!$ok) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired code'], 400);
        }
        $user = User::query()->where('phone', $id)->orWhere('email', strtolower($id))->first();
        if (!$user) {
            // Neutral - do not reveal absence
            return response()->json(['success' => true]);
        }
        $user->password = Hash::make($data['new_password']);
        $user->save();

        // TODO: Invalidate sessions/tokens if using a session or token store
        // e.g., DB sessions table or JWT blacklist rotation

        try {
            if ($user->email) {
                Mail::raw('Your TexaPay password was changed. If this was not you, contact support immediately.', function($m) use ($user) {
                    $m->to($user->email)->subject('TexaPay Password Changed');
                });
            }
        } catch (\Throwable $e) { Log::warning('Password changed email failed: '.$e->getMessage()); }

        return response()->json(['success' => true]);
    }
}
