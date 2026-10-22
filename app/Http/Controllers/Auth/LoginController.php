<?php

namespace App\Http\Controllers\Auth;

use App\Concerns\StructuredLogger;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use StructuredLogger;

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $remember = $request->boolean('remember');
        $email = $validated['email'];

        if (! Auth::attempt($validated, $remember)) {
            $this->logAuthFailure('invalid_credentials', [
                'email' => $email,
                'ip' => $request->ip(),
            ]);

            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        $user = Auth::user();

        if (! $user->agency_id) {
            $this->logAuthFailure('no_agency', [
                'user_id' => $user->id,
                'email' => $email,
                'ip' => $request->ip(),
            ]);

            Auth::logout();

            return back()->with('error', 'Your account is not associated with any agency.');
        }

        if (! $user->agency->isActive) {
            $this->logAuthFailure('agency_inactive', [
                'user_id' => $user->id,
                'agency_id' => $user->agency_id,
                'ip' => $request->ip(),
            ]);

            Auth::logout();

            return back()->with('error', 'Your agency account is not active.');
        }

        $request->session()->regenerate();

        $this->logAuth('login', [
            'user_id' => $user->id,
            'agency_id' => $user->agency_id,
            'ip' => $request->ip(),
            'remember' => $remember,
        ]);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        $this->logAuth('logout', [
            'user_id' => $user?->id,
            'agency_id' => $user?->agency_id,
            'ip' => $request->ip(),
        ]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been logged out.');
    }
}
