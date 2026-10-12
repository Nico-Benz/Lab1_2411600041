<?php

namespace App\Observers;

use App\Models\GradeItem;
use App\Models\GradeTransaction;
use App\Models\User;
use App\Notifications\LowGradeNotification;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;

class GradeTransactionObserver
{
    public function creating(GradeTransaction $transaction): void
    {
        $item = GradeItem::findOrFail($transaction->grade_item_id);

        $previous = (float) $item->current_grade;
        $quantity = (float) $transaction->quantity;

        $new = match ($transaction->type) {
            'increase' => $previous + $quantity,
            'decrease' => $previous - $quantity,
            'adjustment' => $quantity,
        };

        $new = round($new, 2);

        if ($new < 0 || $new > 100) {
            throw new InvalidArgumentException(
                "Invalid grade: {$new}. A grade must stay between 0 and 100."
            );
        }

        $transaction->previous_grade = $previous;
        $transaction->new_grade = $new;
    }

    public function created(GradeTransaction $transaction): void
    {
        $item = $transaction->gradeItem;
        $item->current_grade = $transaction->new_grade;
        $item->save();

        $threshold = (float) $item->low_grade_threshold;
        $wasAtRisk = $this->isAtRisk((float) $transaction->previous_grade, $threshold);
        $isAtRisk = $this->isAtRisk((float) $transaction->new_grade, $threshold);

        if ($isAtRisk && ! $wasAtRisk) {
            Notification::send(User::all(), new LowGradeNotification($item));
        }
    }

    private function isAtRisk(float $grade, float $threshold): bool
    {
        return $grade > 0 && $grade < $threshold;
    }
}