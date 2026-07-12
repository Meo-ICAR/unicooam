<?php

namespace App\Filament\Actions;

use App\Services\ImportPraticheService;
use App\ValueObjects\OamSemester;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Throwable;

class ImportOamAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'importOam';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Importa Pratiche OAM')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('warning')
            ->modalHeading('Importa Pratiche dal Gestionale')
            ->modalDescription('ATTENZIONE! Tutte le modifiche manuali saranno rimosse. ')
            ->modalWidth('md')

            ->action(function (array $data): void {
                $anno = (int) $data['anno'];
                // 1. Generi l'istanza con il metodo statico del tuo Value Object
                $semestre = OamSemester::getInBaseAlMeseCorrente();
                $startAt = $semestre->start;
                $endAt = $semestre->end;

                try {
                    $count = app(ImportPraticheService::class)->import($startAt, $endAt);

                    Notification::make()
                        ->title('Importazione completata')
                        ->body("Importate {$count} pratiche per il {$semestre}° semestre {$anno}.")
                        ->success()
                        ->send();
                } catch (Throwable $e) {
                    Notification::make()
                        ->title('Errore durante l\'importazione')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
