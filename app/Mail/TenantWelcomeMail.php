<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $companyName,
        public string $domain,
        public string $loginUrl,
        public string $username,
        public string $password,
        public bool $requiresActivation = true,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bienvenidos a MANTIS – Credenciales de acceso',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-welcome',
        );
    }
}
