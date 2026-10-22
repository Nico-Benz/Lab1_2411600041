<?php

namespace App\Observers;

use App\Models\Account;
use App\Models\Transaction;
use App\Notifications\LowBalanceNotification;
use Exception;
use Illuminate\Support\Facades\DB;

class AccountObserver
{
    /**
     * Bago i-save ang transaction: tingnan kung pwede.
     */
    public function creating(Transaction $transaction): void
    {
        if ($transaction->type === 'deposit') {
            return;
        }

        $account = Account::findOrFail($transaction->account_id);
        $newBalance = $account->balance - $transaction->amount;

        if ($newBalance < 0 || $newBalance < $account->minimum_balance) {
            throw new Exception('Insufficient funds. Balance cannot go below the minimum balance.');
        }
    }

    /**
     * Pagkatapos ma-save ang transaction: baguhin ang balance.
     */
       public function created(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $account = Account::findOrFail($transaction->account_id);

            if ($transaction->type === 'deposit') {
                $account->balance += $transaction->amount;
            } else {
                $account->balance -= $transaction->amount;
            }

            $account->save();

            // Low balance alert pagkatapos ng withdrawal / transfer
            if ($transaction->type !== 'deposit' && $account->balance <= $account->minimum_balance) {
                $account->user->notify(new LowBalanceNotification($account));
            }
        });
    }
}
