<?php

namespace App\Mail;

use App\Models\PurchaseOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderCreated extends Mailable implements ShouldQueue
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
            subject: 'Purchase Order ' . $this->purchaseOrder->reference_number . ' Created',
        );
    }

    /**
     * Ang view at ang data na ipapasa dito.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.purchase-order-created',
            with: ['order' => $this->purchaseOrder],
        );
    }

    /**
     * Mga attachment. Ilalagay ang PDF ng order dito sa Part 8 (dompdf).
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => Pdf::loadView('exports.purchase-order-pdf', [
                    'order' => $this->purchaseOrder->loadMissing(['supplier', 'createdBy', 'items.product']),
                ])->output(),
                $this->purchaseOrder->reference_number . '.pdf'
            )->withMime('application/pdf'),
        ];
    }
}