<?php

namespace App\Mail;

use App\Models\Team;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TeamInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Team $team,
        public readonly User $inviter,
        public readonly string $token,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.from.address', 'hello@digitalmarketsaas.com'),
                config('mail.from.name', 'DigitalMarketingSaaS')
            ),
            subject: "You've been invited to join {$this->team->name}",
        );
    }

    public function content(): Content
    {
        $acceptUrl = url("/teams/invite/{$this->token}/accept");

        return new Content(
            markdown: 'emails.team-invitation',
            with: [
                'teamName' => $this->team->name,
                'teamDescription' => $this->team->description,
                'inviterName' => $this->inviter->name,
                'token' => $this->token,
                'acceptUrl' => $acceptUrl,
            ],
        );
    }
}
