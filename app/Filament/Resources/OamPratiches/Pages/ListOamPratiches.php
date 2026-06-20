<?php

namespace App\Filament\Resources\OamPratiches\Pages;

use App\Filament\Resources\OamPratiches\OamPraticheResource;
use App\Services\ImportPraticheService;
use App\Services\OamSemestraleService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListOamPratiches extends ListRecords
{
    protected static string $resource = OamPraticheResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //  CreateAction::make(),
            Action::make('riprendiImportazione')
                ->label('Riprendi Importazione')
                ->action(function (ImportPraticheService $service) {
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

                    $count = $service->import($startAt, $endAt);

                    Notification::make()
                        ->title('Importazione Ripresa')
                        ->body("L'importazione è stata ripresa con successo ({$count} righe elaborate).")
                        ->success()
                        ->send();
                }),
            Action::make('rigeneraReport')
                ->label('Ricalcola Aggregati Semestrali')
                ->action(function (OamSemestraleService $service) {
                    $count = $service->aggregate();

                    Notification::make()
                        ->title('Report Aggiornato')
                        ->body("I dati semestrali sono stati ricalcolati con successo ({$count} righe elaborate).")
                        ->success()
                        ->send();
                }),
        ];
    }
}
