<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocumentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $bodyContent;
    public string $subjectLine;
    public array $fileAttachments;

    public function __construct(string $subjectLine, string $bodyContent, array $fileAttachments = [])
    {
        $this->subjectLine = $subjectLine;
        $this->bodyContent = $bodyContent;
        $this->fileAttachments = $fileAttachments;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.blank', // Creeremo questa vista a breve
            with: ['bodyContent' => $this->bodyContent]
        );
    }

    public function attachments(): array
    {
        $attachments = [];
        foreach ($this->fileAttachments as $path) {
            if (filter_var($path, FILTER_VALIDATE_URL)) {
                $attachments[] = Attachment::fromUrl($path);
            } elseif (file_exists($path)) {
                $attachments[] = Attachment::fromPath($path);
            }
        }
        return $attachments;
    }
}