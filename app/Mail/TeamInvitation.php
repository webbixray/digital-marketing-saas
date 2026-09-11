<?php

namespace App\Mail;

use App\Models\Agency;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

class TeamInvitation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Agency $agency,
        public readonly string $inviterName,
        public readonly string $role,
        public readonly string $inviteUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address', 'hello@digitalmarketsaas.com'), config('mail.from.name', 'DigitalMarketingSaaS')),
            subject: "You've been invited to join {$this->agency->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.team-invitation',
            with: [
                'agencyName' => $this->agency->name,
                'inviterName' => $this->inviterName,
                'role' => $this->role,
                'inviteUrl' => $this->inviteUrl,
            ],
        );
    }
}
