<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/** Bell notification for admins: "the scheduled check made N draft purchase orders" */
class ReorderPurchaseOrdersNotification extends Notification
{
    public function __construct(public array $references)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $count = count($this->references);

        return [
            'message' => "{$count} draft purchase order(s) were generated automatically: " . implode(', ', $this->references) . '. Please review.',
            'url' => route('purchase-orders.index', ['status' => 'draft']),
            'count' => $count,
        ];
    }
}
