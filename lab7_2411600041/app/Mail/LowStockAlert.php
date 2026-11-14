<?php

namespace App\Mail;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent to admins when a product drops to its reorder level */
class LowStockAlert extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Product $product, public int $suggestedQuantity = 0)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Low stock: {$this->product->name}");
    }

    public function content(): Content
    {
        $this->product->loadMissing('supplier');

        return new Content(
            view: 'emails.low-stock-alert',
            with: ['product' => $this->product, 'suggestedQuantity' => $this->suggestedQuantity],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
