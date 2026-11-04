<?php

namespace App\Filament\Actions;

use App\Services\ImportSentoviaService;
use App\ValueObjects\OamSemester;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ImportSentoviaAction extends Action
{
    private const DIRECTORY = 'sentovia/upload';

    public static function getDefaultName(): ?string
    {
        return 'importSentovia';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Importa Sentovia')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('warning')
            ->modalHeading('Importa Pratiche da Sentovia')
            ->modalDescription(fn (): string => 'ATTENZIONE! Tutte le modifiche manuali del semestre saranno rimosse. Carica i due file estratti da Sentovia (testo separato da tabulazioni): vengono importati nel semestre '.OamSemester::current()->label().'.')
            ->modalWidth('md')
            ->schema([
                FileUpload::make('pratiche')
                    ->label('File pratiche')
                    ->helperText('Colonne: ID Pratica, Data Inserimento Pratica, Data_erogazione, Data Annullamento, Stato Pratica, Tipo Prodotto, Denominazione Istituto, Importo_Erogazione')
                    ->disk('local')
                    ->directory(self::DIRECTORY)
                    ->acceptedFileTypes(['text/plain', 'text/tab-separated-values', 'text/csv'])
                    ->required(),
                FileUpload::make('provvigioni')
                    ->label('File provvigioni')
                    ->helperText('Colonne: ID Pratica, Tipo, Tipologia Premio, Importo, Data Status')
                    ->disk('local')
                    ->directory(self::DIRECTORY)
                    ->acceptedFileTypes(['text/plain', 'text/tab-separated-values', 'text/csv'])
                    ->required(),
            ])
            ->action(function (array $data): void {
                $disk = Storage::disk('local');

                try {
                    $count = app(ImportSentoviaService::class)->import(
                        OamSemester::current(),
                        $disk->path($data['pratiche']),
                        $disk->path($data['provvigioni']),
                    );

                    Notification::make()
                        ->title('Importazione completata')
                        ->body("Importate {$count} pratiche")
                        ->success()
                        ->send();
                } catch (Throwable $e) {
                    Notification::make()
                        ->title('Errore durante l\'importazione')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                } finally {
                    $disk->delete([$data['pratiche'], $data['provvigioni']]);
                }
            });
    }
}
