<?php

namespace App\Console\Commands;

use App\Filament\Resources\DocumentSchedules\DocumentScheduleResource;
use App\Models\DocumentSchedule;
use Illuminate\Console\Command;

/**
 * Ricalcola lo scadenziario documenti (la tabella che mostra la risorsa e che i controlli leggono). Pianificato ogni notte prima
 * dell'esecuzione dei processi di UnicoBPM: i controlli via API leggono lo scadenziario com'è, senza ricalcolarlo nella richiesta.
 */
class SyncDocumentScheduleCommand extends Command
{
    protected $signature = 'documents:sync-schedule';

    protected $description = 'Ricalcola lo scadenziario dei documenti';

    public function handle(): int
    {
        DocumentScheduleResource::syncScheduleTable();

        $this->info('Scadenziario ricalcolato: '.DocumentSchedule::query()->count().' righe.');

        return self::SUCCESS;
    }
}
