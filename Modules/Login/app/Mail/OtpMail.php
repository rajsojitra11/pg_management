<?php

namespace Modules\Login\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $otp,
        public string $name,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your TenantFlow Login OTP',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'login::emails.otp',
        );
    }
}
