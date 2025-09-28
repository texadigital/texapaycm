<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Transfer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required','string','max:190'],
            'phone' => ['required','string','max:32','unique:users,phone'],
            'password' => ['required','string','min:6','max:190','confirmed'],
            'pin' => ['required','string','regex:/^\d{4,6}$/'],
        ]);

        $phone = preg_replace('/\D+/', '', $validated['phone']);

        // Email is mandatory in the current schema; use a placeholder derived from phone
        $email = $phone . '@local';

        $user = User::create([
            'name' => $validated['name'],
            'phone' => $phone,
            'email' => $email,
            'password' => Hash::make($validated['password']),
            'pin_hash' => Hash::make($validated['pin']),
        ]);

        Auth::login($user);
        return redirect()->route('dashboard')->with('success', 'Welcome to TexaPay!');
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'phone' => ['required','string','max:32'],
            'password' => ['required','string','max:190'],
        ]);
        $phone = preg_replace('/\D+/', '', $credentials['phone']);

        $user = User::where('phone', $phone)->first();
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return back()->withInput()->withErrors(['phone' => 'Invalid phone or password']);
        }

        Auth::login($user, true);
        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login.show');
    }

    public function deleteAccount(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required','string'],
        ]);
        $user = $request->user();
        if (!$user || !Hash::check($request->string('password'), $user->password)) {
            return back()->with('error', 'Password is incorrect.');
        }
        // Soft delete user
        $user->delete();
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login.show')->with('success', 'Account deleted.');
    }
}
