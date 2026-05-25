<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
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
            subject: 'Código de Recuperación de Contraseña',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: "<div style='font-family: sans-serif;'><h1>Recuperación de Contraseña</h1><p>Tu código de recuperación es: <strong>{$this->otp}</strong></p><p>Este código expira en 15 minutos.</p></div>"
        );
    }
}
