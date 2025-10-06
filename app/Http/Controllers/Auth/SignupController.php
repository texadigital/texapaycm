<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class SignupController extends Controller
{
    public function request(Request $request, OtpService $otp, SmsService $sms)
    {
        $data = $request->validate([
            'phone' => ['required','string','min:6','max:32'],
        ]);
        $id = $otp->normalizeIdentifier($data['phone']);
        $res = $otp->create(OtpService::PURPOSE_SIGNUP, $id, 300);
        $sms->send($id, "Your TexaPay signup code is {$res['code']}. It expires in 5 minutes.");
        // Neutral response (avoid enumeration of existing accounts)
        return response()->json(['success' => true, 'message' => 'If this number can be registered, a code has been sent.']);
    }

    public function resend(Request $request, OtpService $otp, SmsService $sms)
    {
        $data = $request->validate([
            'phone' => ['required','string','min:6','max:32'],
        ]);
        $id = $otp->normalizeIdentifier($data['phone']);
        $res = $otp->create(OtpService::PURPOSE_SIGNUP, $id, 300);
        $sms->send($id, "Your TexaPay signup code is {$res['code']}. It expires in 5 minutes.");
        return response()->json(['success' => true]);
    }

    public function verify(Request $request, OtpService $otp)
    {
        $data = $request->validate([
            'phone' => ['required','string','min:6','max:32'],
            'code' => ['required','string','min:4','max:8'],
            'password' => ['required','string','min:8','max:128'],
            'first_name' => ['required','string','max:100'],
            'last_name' => ['required','string','max:100'],
            'dob' => ['required','date'],
            'username' => ['nullable','string','min:3','max:32','regex:/^[a-z0-9_.-]+$/i', Rule::unique('users','username')->whereNull('deleted_at')],
            'email' => ['nullable','email','max:191', Rule::unique('users','email')->whereNull('deleted_at')],
        ]);
        $id = $otp->normalizeIdentifier($data['phone']);
        $digits = preg_replace('/\D+/', '', $id);
        $ok = $otp->verify(OtpService::PURPOSE_SIGNUP, $id, $data['code']);
        if (!$ok) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired code'], 400);
        }
        // Match existing users stored with numeric phone or E.164 string
        $existing = User::query()
            ->where('phone', $id)
            ->orWhere('phone', $digits)
            ->first();
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Phone already registered',
                'code' => 'PHONE_TAKEN',
            ], 409);
        }
        $user = new User();
        // Persist digits-only to be compatible with schemas using numeric/varchar without '+'
        $user->phone = $digits;
        $user->first_name = $data['first_name'];
        $user->last_name = $data['last_name'];
        $user->dob = $data['dob'];
        if (!empty($data['username'])) $user->username = $data['username'];
        if (!empty($data['email'])) $user->email = $data['email'];
        $user->password = Hash::make($data['password']);
        if (!$user->exists) {
            // If there is a default name column used by app, fill it from first/last
            if (property_exists($user, 'name') || \Schema::hasColumn($user->getTable(),'name')) {
                $user->name = trim($user->first_name.' '.$user->last_name);
            }
        }
        $user->save();

        return response()->json(['success' => true, 'userId' => $user->id]);
    }
}
