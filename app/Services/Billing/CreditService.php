<?php

namespace App\Services\Billing;

use App\Models\CreditTransaction;
use App\Models\MeteredUsage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreditService
{
    public function getBalance(int $agencyId): int
    {
        $latest = CreditTransaction::where('agency_id', $agencyId)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        if ($latest) {
            return $latest->balance_after;
        }

        return 0;
    }

    public function addCredits(int $agencyId, int $amount, string $description, ?int $userId = null, array $metadata = []): CreditTransaction
    {
        return DB::transaction(function () use ($agencyId, $amount, $description, $userId, $metadata) {
            $currentBalance = $this->getBalance($agencyId);
            $newBalance = $currentBalance + $amount;

            $transaction = CreditTransaction::create([
                'agency_id' => $agencyId,
                'user_id' => $userId,
                'type' => 'purchase',
                'amount' => $amount,
                'balance_after' => $newBalance,
                'description' => $description,
                'metadata' => $metadata,
            ]);

            Log::info('Credits added', [
                'agency_id' => $agencyId,
                'amount' => $amount,
                'new_balance' => $newBalance,
                'transaction_id' => $transaction->id,
            ]);

            return $transaction;
        });
    }

    public function useCredits(int $agencyId, int $amount, string $description, ?int $userId = null, array $metadata = []): ?CreditTransaction
    {
        return DB::transaction(function () use ($agencyId, $amount, $description, $userId, $metadata) {
            $currentBalance = $this->getBalance($agencyId);

            if ($currentBalance < $amount) {
                Log::warning('Insufficient credits', [
                    'agency_id' => $agencyId,
                    'requested' => $amount,
                    'available' => $currentBalance,
                ]);
                return null;
            }

            $newBalance = $currentBalance - $amount;

            $transaction = CreditTransaction::create([
                'agency_id' => $agencyId,
                'user_id' => $userId,
                'type' => 'usage',
                'amount' => -$amount,
                'balance_after' => $newBalance,
                'description' => $description,
                'metadata' => $metadata,
            ]);

            Log::info('Credits used', [
                'agency_id' => $agencyId,
                'amount' => $amount,
                'new_balance' => $newBalance,
                'transaction_id' => $transaction->id,
            ]);

            return $transaction;
        });
    }

    public function refundCredits(int $agencyId, int $amount, string $description, ?int $userId = null, array $metadata = []): CreditTransaction
    {
        return DB::transaction(function () use ($agencyId, $amount, $description, $userId, $metadata) {
            $currentBalance = $this->getBalance($agencyId);
            $newBalance = $currentBalance + $amount;

            $transaction = CreditTransaction::create([
                'agency_id' => $agencyId,
                'user_id' => $userId,
                'type' => 'refund',
                'amount' => $amount,
                'balance_after' => $newBalance,
                'description' => $description,
                'metadata' => $metadata,
            ]);

            Log::info('Credits refunded', [
                'agency_id' => $agencyId,
                'amount' => $amount,
                'new_balance' => $newBalance,
                'transaction_id' => $transaction->id,
            ]);

            return $transaction;
        });
    }

    public function getTransactionHistory(int $agencyId, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return CreditTransaction::where('agency_id', $agencyId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getCreditSummary(int $agencyId): array
    {
        $transactions = CreditTransaction::where('agency_id', $agencyId);

        return [
            'balance' => $this->getBalance($agencyId),
            'total_purchased' => (clone $transactions)->whereIn('type', ['purchase', 'bonus'])->sum('amount'),
            'total_used' => (int) abs((clone $transactions)->where('type', 'usage')->sum('amount')),
            'total_refunded' => (clone $transactions)->where('type', 'refund')->sum('amount'),
            'transaction_count' => (clone $transactions)->count(),
        ];
    }

    public function getMonthlySpend(int $agencyId): int
    {
        return (int) CreditTransaction::where('agency_id', $agencyId)
            ->where('type', 'usage')
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum(\DB::raw('ABS(amount)'));
    }
}
