<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $otp;

    public function __construct(string $otp)
    {
        $this->otp = $otp;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verifica tu Correo Electrónico',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: "<div style='font-family: sans-serif;'><h1>Bienvenido a la App</h1><p>Tu código de verificación es: <strong>{$this->otp}</strong></p><p>Ingrésalo en la aplicación para activar tu cuenta.</p></div>"
        );
    }
}
