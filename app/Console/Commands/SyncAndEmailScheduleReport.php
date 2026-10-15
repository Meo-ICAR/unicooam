<?php

namespace App\Console\Commands;

use App\Filament\Resources\DocumentSchedules\DocumentScheduleResource;
use Illuminate\Console\Command;

class SyncAndEmailScheduleReport extends Command
{
    protected $signature = 'documents:sync-and-email';

    protected $description = 'Aggiorna lo scadenziario documenti e invia via email l\'export Excel (come il bottone «Aggiorna scadenziario» + «Esporta Excel»)';

    public function handle(): int
    {
        DocumentScheduleResource::syncAndEmailScheduleReport();

        $this->info('Scadenziario aggiornato e report inviato.');

        return self::SUCCESS;
    }
}
