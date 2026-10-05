<?php

namespace App\Filament\Actions;

use App\Filament\Exports\OamSemestraleExport;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportOamAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'exportOam';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Esporta Relazione OAM')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('success')
            ->modalHeading('Esporta Relazione Semestrale OAM')
            ->modalDescription('Genera il file Excel (.xlsx) con i 5 fogli richiesti dalla segnalazione OAM.')
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
            ->action(function (array $data): BinaryFileResponse {
                $anno = (int) $data['anno'];
                $semestre = (int) $data['semestre'];

                $filename = sprintf(
                    'OAM_Semestrale_%d_%dSem.xlsx',
                    $anno,
                    $semestre,
                );

                return Excel::download(
                    new OamSemestraleExport($anno, $semestre),
                    $filename,
                );
            });
    }
}
