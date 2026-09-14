<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProducerTrainingSummaryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $bodyContent,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Situazione formazione produttori',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.blank',
            with: ['bodyContent' => $this->bodyContent],
        );
    }
}
