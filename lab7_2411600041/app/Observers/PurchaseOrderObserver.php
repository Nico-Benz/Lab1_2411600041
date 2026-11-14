<?php

namespace App\Observers;

use App\Mail\PurchaseOrderApproved;
use App\Mail\PurchaseOrderCreated;
use App\Mail\PurchaseOrderSent;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Watches the purchase_orders table:
 *  - a new order      -> history line + email to admins and managers
 *  - status changed   -> history line + email (approved -> creator, sent -> supplier)
 * Emails are queued, and a failed email never breaks the order itself.
 */
class PurchaseOrderObserver
{
    public function created(PurchaseOrder $order): void
    {
        $order->addLog('created', null, $order->status, null, $order->created_by);

        $recipients = User::whereIn('role', ['admin', 'manager'])->pluck('email')->all();

        $this->queueMail($recipients, new PurchaseOrderCreated($order));
    }

    public function updated(PurchaseOrder $order): void
    {
        if (! $order->wasChanged('status')) {
            return;
        }

        $from = $order->getOriginal('status');
        $to = $order->status;

        $comment = $to === 'rejected' ? $order->rejection_reason : null;
        $order->addLog($to, $from, $to, $comment, Auth::id());

        if ($to === 'approved' && $order->createdBy?->email) {
            $this->queueMail([$order->createdBy->email], new PurchaseOrderApproved($order));
        }

        if ($to === 'sent' && $order->supplier?->email) {
            $this->queueMail([$order->supplier->email], new PurchaseOrderSent($order));
        }
    }

    private function queueMail(array $recipients, $mailable): void
    {
        $recipients = array_values(array_filter($recipients));

        if ($recipients === []) {
            return;
        }

        try {
            Mail::to($recipients)->queue($mailable);
        } catch (Throwable $e) {
            report($e); // write the problem to laravel.log, but keep going
        }
    }
}
