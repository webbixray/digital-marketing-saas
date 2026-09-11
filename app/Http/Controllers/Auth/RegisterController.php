<?php

namespace App\Http\Controllers\Auth;

use App\Concerns\StructuredLogger;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    use StructuredLogger;

    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'agency_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $referralService = app(ReferralService::class);
            $referralCode = $request->query('ref') ?? session('referral_code');

            $user = DB::transaction(function () use ($validated, $referralService, $referralCode) {
                $agency = Agency::create([
                    'name' => $validated['agency_name'],
                    'slug' => Str::slug($validated['agency_name']).'-'.uniqid(),
                    'email' => $validated['email'],
                    'status' => 'active',
                    'subscription_plan' => 'free',
                    'subscription_status' => 'active',
                    'is_active' => true,
                ]);

                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'agency_id' => $agency->id,
                    'role' => 'owner',
                    'is_active' => true,
                    'is_approved' => true,
                ]);

                // Assign referral code to user
                $referralService->assignCode($user);
                $referralService->assignCodeToAgency($agency);

                // Process referral if code exists
                if ($referralCode) {
                    $referralService->processReferral($referralCode, $user);
                }

                return $user;
            });

            Auth::login($user);

            // Trigger email verification notification
            event(new Registered($user));

            $this->logAuth('registration', [
                'user_id' => $user->id,
                'agency_id' => $user->agency_id,
                'email' => $user->email,
                'ip' => $request->ip(),
            ]);

            return redirect()->route('verification.notice')->with('success', 'Welcome! Your agency has been created. Please verify your email address. After that, we\'ll help you get set up in 5 easy steps.');
        } catch (\Exception $e) {
            Log::error('Registration failed', [
                'email' => $validated['email'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Registration failed. Please try again.')->withInput();
        }
    }
}
