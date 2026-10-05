<?php
namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OtpMail extends Mailable
{
    public function __construct(
        public string $code,
        public string $purpose,
        public string $name = '',
        public int $minutes = 10,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->purpose === 'reset_password'
            ? $this->code . ' is your MediCare password reset code'
            : $this->code . ' is your MediCare verification code');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.otp');
    }
}
