<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

class ContactFormSubmission extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $email,
        public readonly string $subject,
        public readonly string $message,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address', 'hello@digitalmarketsaas.com'), config('mail.from.name', 'DigitalMarketingSaaS')),
            replyTo: [$this->email => "{$this->firstName} {$this->lastName}"],
            subject: "Contact: {$this->subject}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.contact-form',
            with: [
                'firstName' => $this->firstName,
                'lastName' => $this->lastName,
                'email' => $this->email,
                'subject' => $this->subject,
                'message' => $this->message,
            ],
        );
    }
}
