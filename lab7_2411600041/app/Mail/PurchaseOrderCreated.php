<?php

namespace App\Mail;

use App\Models\PurchaseOrder;
use App\Services\PdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent to admins and managers when a new purchase order is created (with the PDF attached) */
class PurchaseOrderCreated extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public PurchaseOrder $purchaseOrder)
    {
        $this->afterCommit(); // wait until the order and its items are saved
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Purchase Order {$this->purchaseOrder->reference_number} created");
    }

    public function content(): Content
    {
        $this->purchaseOrder->loadMissing(['supplier', 'items.product', 'createdBy']);

        return new Content(
            view: 'emails.purchase-order-created',
            with: ['po' => $this->purchaseOrder],
        );
    }

    public function attachments(): array
    {
        if (! PdfService::available()) {
            return [];
        }

        $po = $this->purchaseOrder->loadMissing(['supplier', 'items.product', 'createdBy']);

        return [
            Attachment::fromData(fn () => PdfService::output('pdf.purchase-order', ['po' => $po]), "{$po->reference_number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
