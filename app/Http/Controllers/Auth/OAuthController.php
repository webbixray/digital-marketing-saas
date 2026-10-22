<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class OAuthController extends Controller
{
    /**
     * Redirect to provider.
     */
    public function redirect(string $provider)
    {
        $allowedProviders = ['google', 'facebook', 'twitter', 'linkedin'];

        if (! in_array($provider, $allowedProviders)) {
            abort(404);
        }

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle provider callback.
     */
    public function callback(string $provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', "Failed to authenticate with {$provider}.");
        }

        $user = User::where('email', $socialUser->getEmail())->first();

        if ($user) {
            Auth::login($user);

            return redirect()->route('dashboard');
        }

        // Create new user
        $user = User::create([
            'name' => $socialUser->getName() ?? $socialUser->getNickname(),
            'email' => $socialUser->getEmail(),
            'password' => Hash::make(uniqid()),
            'email_verified_at' => now(),
        ]);

        Auth::login($user);

        return redirect()->route('onboarding');
    }
}
