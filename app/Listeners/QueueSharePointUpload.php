<?php

namespace App\Listeners;

use App\Enums\SyncStatus;
use App\Jobs\UploadDocumentToSharePoint;
use App\Models\Document;
use App\Services\SharePoint\SharePointUploader;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;

class QueueSharePointUpload
{
    public function __construct(private readonly SharePointUploader $uploader) {}

    public function handle(MediaHasBeenAddedEvent $event): void
    {
        $document = $event->media->model;

        // Senza credenziali il documento resta solo locale.
        if (! $document instanceof Document || $event->media->collection_name !== 'documents' || ! $this->uploader->isConfigured()) {
            return;
        }

        $document->forceFill(['sync_status' => SyncStatus::LOCAL->value, 'app_id' => null])->saveQuietly();

        UploadDocumentToSharePoint::dispatch($document->id)->afterCommit();
    }
}
