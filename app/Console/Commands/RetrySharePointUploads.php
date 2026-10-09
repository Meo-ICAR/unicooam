<?php

namespace App\Console\Commands;

use App\Enums\SyncStatus;
use App\Jobs\UploadDocumentToSharePoint;
use App\Models\Document;
use Illuminate\Console\Command;

class RetrySharePointUploads extends Command
{
    protected $signature = 'sharepoint:retry-uploads {--local : Include anche i documenti rimasti solo locali}';

    protected $description = 'Rimette in coda i documenti con upload su SharePoint fallito';

    public function handle(): int
    {
        $statuses = [SyncStatus::FAILED, ...($this->option('local') ? [SyncStatus::LOCAL] : [])];

        $ids = Document::whereIn('sync_status', array_map(fn (SyncStatus $s) => $s->value, $statuses))
            ->whereHas('media', fn ($q) => $q->where('collection_name', 'documents'))
            ->pluck('id');

        foreach ($ids as $id) {
            UploadDocumentToSharePoint::dispatch($id);
        }

        $this->info("{$ids->count()} documenti rimessi in coda.");

        return self::SUCCESS;
    }
}
