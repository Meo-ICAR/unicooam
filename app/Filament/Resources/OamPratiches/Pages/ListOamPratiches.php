<?php

namespace App\Filament\Resources\OamPratiches\Pages;

use App\Filament\Resources\OamPratiches\OamPraticheResource;
use App\Services\OamSemestraleService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

// CORRETTO

class ListOamPratiches extends ListRecords
{
    protected static string $resource = OamPraticheResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //  CreateAction::make(),
            Action::make('storno')
                ->label('Aggiungi storno')
                ->action(

                    Notification::make()
                        ->title('Aggiunta pratica stornata')
                        ->body('Storno aggiunto con successo')
                        ->success()
                        ->send()
                ),

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
