<?php

namespace App\Mail;

use App\Models\GradeItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LowStockAlert extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Tumatanggap ng GradeItem (subject) na At Risk.
     */
    public function __construct(public GradeItem $gradeItem)
    {
    }

    /**
     * Ang subject line ng email.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'At Risk: ' . $this->gradeItem->subject_code . ' is below the threshold',
        );
    }

    /**
     * Ang view at ang data na ipapasa dito.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.low-stock-alert',
            with: ['item' => $this->gradeItem],
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