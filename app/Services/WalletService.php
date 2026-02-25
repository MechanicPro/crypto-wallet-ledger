<?php
namespace App\Services;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Enums\TransactionType;
use App\Enums\TransactionStatus;
use Illuminate\Support\Facades\DB;
use Exception;

class WalletService
{
    public function deposit(int $userId, string $currency, string $amount, string $txHash): WalletTransaction
    {
        return DB::transaction(function () use ($userId, $currency, $amount, $txHash) {
            if (WalletTransaction::where('tx_hash', $txHash)->exists()) {
                throw new Exception("Transaction already processed.");
            }

            $wallet = Wallet::firstOrCreate(['user_id' => $userId, 'currency' => $currency])->lockForUpdate()->first();
            $wallet->balance = bcadd($wallet->balance, $amount, 18);
            $wallet->save();

            return $wallet->transactions()->create([
                'type' => TransactionType::DEPOSIT,
                'status' => TransactionStatus::COMPLETED,
                'amount' => $amount,
                'tx_hash' => $txHash,
            ]);
        });
    }

    public function initiateWithdrawal(int $userId, string $currency, string $amount, string $address): WalletTransaction
    {
        return DB::transaction(function () use ($userId, $currency, $amount, $address) {
            $wallet = Wallet::where('user_id', $userId)->where('currency', $currency)->lockForUpdate()->firstOrFail();

            if (bccomp($wallet->balance, $amount, 18) === -1) {
                throw new Exception("Insufficient funds.");
            }

            $wallet->balance = bcsub($wallet->balance, $amount, 18);
            $wallet->locked_balance = bcadd($wallet->locked_balance, $amount, 18);
            $wallet->save();

            return $wallet->transactions()->create([
                'type' => TransactionType::WITHDRAWAL,
                'status' => TransactionStatus::PENDING,
                'amount' => $amount,
                'meta' => ['to_address' => $address]
            ]);
        });
    }
}
