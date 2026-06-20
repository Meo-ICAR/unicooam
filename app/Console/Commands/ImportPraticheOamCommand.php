<?php

namespace App\Console\Commands;

use App\Services\ImportPraticheService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ImportPraticheOamCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'oam:import-pratiche';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa le pratiche da PROFORMA a OamPratiche filtrandole in base alla data corrente';

    /**
     * Execute the console command.
     */
    public function handle(ImportPraticheService $service)
    {
        $now = Carbon::now();
        $currentYear = $now->year;

        if ($now->month < 4) {
            // Se siamo prima di aprile (es. gen-mar), prendiamo il secondo semestre dell'anno precedente
            $startAt = Carbon::create($currentYear - 1, 7, 1)->startOfDay();
            $endAt = Carbon::create($currentYear, 1, 1)->startOfDay();
        } else {
            // Altrimenti prendiamo il primo semestre dell'anno corrente
            $startAt = Carbon::create($currentYear, 1, 1)->startOfDay();
            $endAt = Carbon::create($currentYear, 7, 1)->startOfDay();
        }

        $this->info("Avvio importazione pratiche dal {$startAt->format('d/m/Y')} al {$endAt->format('d/m/Y')}");

        // NOTA: Il service attualmente accetta solo $startAt dopo le tue ultime modifiche.
        // Assicurati che accetti anche $endAt se vuoi filtrare anche per data di fine.
        // Qui lo passo al metodo, potresti dover aggiornare la firma del metodo import nel service.
        try {
            $importedCount = $service->import($startAt, $endAt);
            $this->info("Importazione completata con successo! Record importati: {$importedCount}");
        } catch (\Throwable $e) {
            $this->error("Errore durante l'importazione: " . $e->getMessage());
        }
    }
}
