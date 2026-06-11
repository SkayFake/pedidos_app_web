<?php

namespace App\Mail;

use App\Models\Coupon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CouponReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Coupon $coupon,
        public string $customMessage = ''
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '¡Tienes un nuevo cupón de regalo!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.coupon-received',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
