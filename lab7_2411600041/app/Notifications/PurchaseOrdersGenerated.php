<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class PurchaseOrdersGenerated extends Notification
{
    /**
     * @param  int  $count       ilang draft PO ang nagawa
     * @param  array  $references  mga reference number ng mga PO
     */
    public function __construct(
        public int $count,
        public array $references = []
    ) {
    }

    /**
     * Sa bell (database) lang ipapadala.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Ang laman ng notification na lalabas sa bell.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => $this->count . ' draft purchase order(s) auto-generated: ' . implode(', ', $this->references),
        ];
    }
}