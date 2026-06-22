<?php

namespace App\Filament\Actions;

use App\Services\ImportPraticheService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
            ->modalDescription('Seleziona il semestre e l\'anno per avviare l\'importazione delle pratiche e il ricalcolo degli aggregati OAM.')
            ->modalWidth('md')
            ->form([
                Select::make('semestre')
                    ->label('Semestre')
                    ->options([
                        1 => '1° Semestre (Gennaio – Giugno)',
                        2 => '2° Semestre (Luglio – Dicembre)',
                    ])
                    ->default(now()->month <= 6 ? 1 : 2)
                    ->required(),
                TextInput::make('anno')
                    ->label('Anno')
                    ->numeric()
                    ->minValue(2020)
                    ->maxValue(now()->year)
                    ->default(now()->year)
                    ->required(),
            ])
            ->action(function (array $data): void {
                $anno = (int) $data['anno'];
                $semestre = (int) $data['semestre'];

                if ($semestre === 1) {
                    $startAt = Carbon::create($anno, 1, 1)->startOfDay();
                    $endAt = Carbon::create($anno, 6, 30)->endOfDay();
                } else {
                    $startAt = Carbon::create($anno, 7, 1)->startOfDay();
                    $endAt = Carbon::create($anno, 12, 31)->endOfDay();
                }

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
