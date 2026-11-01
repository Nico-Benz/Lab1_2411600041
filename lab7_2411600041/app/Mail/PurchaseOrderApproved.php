<?php

namespace App\Mail;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderApproved extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Tumatanggap ng PurchaseOrder sa constructor.
     */
    public function __construct(public PurchaseOrder $purchaseOrder)
    {
    }

    /**
     * Ang subject line ng email.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Purchase Order ' . $this->purchaseOrder->reference_number . ' Approved',
        );
    }

    /**
     * Ang view at ang data na ipapasa dito.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.purchase-order-approved',
            with: ['order' => $this->purchaseOrder],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}