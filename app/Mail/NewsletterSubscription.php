<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewsletterSubscription extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $email,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address', 'hello@digitalmarketsaas.com'), config('mail.from.name', 'DigitalMarketingSaaS')),
            subject: 'Welcome to Our Newsletter!',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.newsletter-subscription',
            with: [
                'email' => $this->email,
            ],
        );
    }
}
