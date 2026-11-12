<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Product $product)
    {
        // Send only after the database transaction is committed
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Low stock: ' . $this->product->name)
            ->line("{$this->product->name} ({$this->product->sku}) is low on stock.")
            ->line("Current quantity: {$this->product->quantity}. Reorder level: {$this->product->reorder_level}.")
            ->action('View product', route('products.show', $this->product))
            ->line('Suggested action: reorder this item from ' . ($this->product->supplier ?? 'your supplier') . '.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'sku' => $this->product->sku,
            'quantity' => $this->product->quantity,
            'reorder_level' => $this->product->reorder_level,
            'message' => "{$this->product->name} ({$this->product->sku}) is low: {$this->product->quantity} left, reorder at {$this->product->reorder_level}. Reorder.",
        ];
    }
}
