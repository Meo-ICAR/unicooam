<?php

namespace App\Jobs;

use App\Enums\SyncStatus;
use App\Models\Document;
use App\Services\SharePoint\SharePointUploader;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class UploadDocumentToSharePoint implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @return list<int> */
    public function backoff(): array
    {
        return [30, 120];
    }

    public function __construct(public readonly string $documentId) {}

    public function handle(SharePointUploader $uploader): void
    {
        $document = Document::find($this->documentId);
        $media = $document?->getFirstMedia('documents');

        if ($document === null || $media === null || ! $uploader->isConfigured()) {
            return;
        }

        if ($document->sync_status === SyncStatus::SYNCED->value && filled($document->app_id)) {
            return;
        }

        $document->forceFill(['sync_status' => SyncStatus::SYNCING->value])->saveQuietly();

        $result = $uploader->upload($media->getPath(), $this->folders($document, $uploader), $media->file_name);

        $webUrl = $result['web_url'];
        $document->forceFill([
            'sync_status' => SyncStatus::SYNCED->value,
            'source_app' => 'sharepoint',
            'app_id' => $result['id'],
            'app_drive_id' => $result['drive_id'],
            'app_etag' => $result['etag'],
            // document_url è varchar(255): l'URL completo resta sempre in metadata.web_url.
            'document_url' => $webUrl !== null && strlen($webUrl) <= 255 ? $webUrl : $document->document_url,
            'metadata' => array_merge($document->metadata ?? [], ['web_url' => $webUrl, 'sync_error' => null]),
        ])->saveQuietly();
    }

    public function failed(Throwable $e): void
    {
        $document = Document::find($this->documentId);

        $document?->forceFill([
            'sync_status' => SyncStatus::FAILED->value,
            'metadata' => array_merge($document->metadata ?? [], ['sync_error' => $e->getMessage()]),
        ])->saveQuietly();
    }

    /** @return list<string> */
    private function folders(Document $document, SharePointUploader $uploader): array
    {
        $owner = $document->documentable;
        $label = $owner?->name ?? $owner?->nome ?? $owner?->ragione_sociale
            ?? trim(($document->documentable_type ?? 'documento').'-'.($document->documentable_id ?? $document->id), '-');

        return [(string) config('services.sharepoint.upload_root'), $uploader->sanitize((string) $label)];
    }
}
