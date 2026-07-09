<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Spatie\MediaLibrary\MediaCollections\Models\Media; // <-- Importante: permette di riconoscere l'oggetto Spatie

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
        $this->fileAttachments = $fileAttachments; // Ora può ricevere stringhe o oggetti Media
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
            view: 'emails.blank',
            with: ['bodyContent' => $this->bodyContent]
        );
    }

    public function attachments(): array
    {
        $attachments = [];

        foreach ($this->fileAttachments as $file) {
            // 1. Se l'allegato è un oggetto Media di Spatie, usiamo il suo metodo nativo
            if ($file instanceof Media) {
                $attachments[] = $file->mailAttachment();
            }
            // 2. Se è una stringa ed è un URL valido
            elseif (is_string($file) && filter_var($file, FILTER_VALIDATE_URL)) {
                $attachments[] = Attachment::fromUrl($file);
            }
            // 3. Se è una stringa ed è un percorso file locale esistente
            elseif (is_string($file) && file_exists($file)) {
                $attachments[] = Attachment::fromPath($file);
            }
        }

        return $attachments;
    }
}
