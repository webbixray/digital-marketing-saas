<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WinBackEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $discountCode,
        public readonly int $discountPercent,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address', 'hello@digitalmarketsaas.com'), config('mail.from.name', 'DigitalMarketingSaaS')),
            subject: "We miss you! Here's {$this->discountPercent}% off to come back",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.win-back',
            with: [
                'name' => $this->user->name,
                'discountCode' => $this->discountCode,
                'discountPercent' => $this->discountPercent,
            ],
        );
    }
}
