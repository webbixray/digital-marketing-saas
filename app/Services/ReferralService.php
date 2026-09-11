<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ReferralService
{
    private const CREDIT_AMOUNT = 10.00;
    private const REFERRER_REWARD = 10.00;

    /**
     * Generate a unique referral code.
     */
    public function generateCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (User::where('referral_code', $code)->exists() || Agency::where('referral_code', $code)->exists());

        return $code;
    }

    /**
     * Assign referral code to user.
     */
    public function assignCode(User $user): void
    {
        if (!$user->referral_code) {
            $user->referral_code = $this->generateCode();
            $user->save();
        }
    }

    /**
     * Assign referral code to agency.
     */
    public function assignCodeToAgency(Agency $agency): void
    {
        if (!$agency->referral_code) {
            $agency->referral_code = $this->generateCode();
            $agency->save();
        }
    }

    /**
     * Process a referral when a new user registers.
     */
    public function processReferral(string $referralCode, User $newUser): bool
    {
        $referrer = User::where('referral_code', $referralCode)->first();

        if (!$referrer || $referrer->id === $newUser->id) {
            return false;
        }

        $newUser->referred_by = $referrer->id;
        $newUser->save();

        Log::info('Referral processed', [
            'referrer_id' => $referrer->id,
            'new_user_id' => $newUser->id,
            'code' => $referralCode,
        ]);

        return true;
    }

    /**
     * Award credits when a referred user makes their first payment.
     */
    public function awardReferralCredits(User $user): void
    {
        if (!$user->referred_by) {
            return;
        }

        $referrer = User::find($user->referred_by);
        if (!$referrer) {
            return;
        }

        // Award credits to referrer
        $referrer->credits += self::REFERRER_REWARD;
        $referrer->referral_count++;
        $referrer->save();

        // Award credits to new user
        $user->credits += self::CREDIT_AMOUNT;
        $user->save();

        Log::info('Referral credits awarded', [
            'referrer_id' => $referrer->id,
            'new_user_id' => $user->id,
            'amount' => self::CREDIT_AMOUNT,
        ]);
    }

    /**
     * Get referral statistics for a user.
     */
    public function getStats(User $user): array
    {
        $referrals = User::where('referred_by', $user->id)->get();

        return [
            'referral_code' => $user->referral_code,
            'referral_link' => url('/register?ref=' . $user->referral_code),
            'total_referrals' => $referrals->count(),
            'total_credits' => $user->credits,
            'referrals' => $referrals->map(fn ($r) => [
                'name' => $r->name,
                'email' => $r->email,
                'joined_at' => $r->created_at->toDateString(),
                'paid' => $r->first_paid_at !== null,
            ]),
        ];
    }

    /**
     * Apply credits to a user's account.
     */
    public function applyCredits(User $user, float $amount): bool
    {
        if ($user->credits < $amount) {
            return false;
        }

        $user->credits -= $amount;
        $user->save();

        return true;
    }
}
