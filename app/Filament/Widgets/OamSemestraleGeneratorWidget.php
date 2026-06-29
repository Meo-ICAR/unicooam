<?php

namespace App\Filament\Widgets;

use App\Filament\Exports\OamSemestraleExport;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Widgets\Widget;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OamSemestraleGeneratorWidget extends Widget implements HasActions
{
    use InteractsWithActions;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.oam-semestrale-generator-widget';

    /**
     * Registriamo l'azione nel Widget
     */
    protected function getActions(): array
    {
        return [
            $this->exportOamAction(),
        ];
    }

    /**
     * Configurazione dell'Azione con il Form Modale
     */
    public function exportOamAction(): Action
    {
        return Action::make('exportOam')
            ->modalHeading('Esporta Relazione Semestrale OAM')
            ->modalDescription("Seleziona il semestre e l'anno per generare il file Excel (.xlsx).")
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

                $filename = sprintf('OAM_Semestrale_%d_%dSem.xlsx', $anno, $semestre);

                return Excel::download(
                    new OamSemestraleExport($anno, $semestre),
                    $filename
                );
            });
    }
}
