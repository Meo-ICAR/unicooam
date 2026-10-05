<?php

namespace App\Filament\Resources\SuspiciousActivityReports\Schemas;

use App\Models\PROFORMA\Fornitore;
use App\Models\Company;
use App\Models\Employee;
use Filament\Forms\Components\MorphToSelect\Type;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SuspiciousActivityReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // ==========================================
                // COLUMN LEFT: Dati principali della segnalazione (Prende 2/3)
                // ==========================================
                Section::make('Dettagli della Segnalazione')
                    ->description("Inserisci i dati relativi all'attività sospetta riscontrata.")
                    ->columnSpan(2)
                    ->schema([
                        Grid::make(2)->schema([
                            // 1. SEGNALATORE POLIMORFICO (Agent o Employee)
                            MorphToSelect::make('reportable')
                                ->label('Soggetto Segnalatore')
                                ->required()
                                ->columnSpanFull()
                                ->types([
                                    Type::make(Employee::class)
                                        ->label('Dipendente')
                                        //    ->whereNull('termination_date')
                                        ->titleAttribute('name'),
                                    Type::make(Fornitore::class)
                                        //   ->whereNull('dismissed_at')
                                        ->label('Agente / Produttore')
                                        ->titleAttribute('name'),
                                ]),
                            // 2. CORRELAZIONE MANDANTE (Opzionale)
                            Select::make('client_id')
                                ->label('Mandante Correlato')
                                ->relationship('client', 'name')
                                ->searchable()
                                ->preload()
                                ->nullable(),
                            // 3. DATA DELLA SEGNALAZIONE
                            DateTimePicker::make('reported_at')
                                ->label('Data e Ora Segnalazione')
                                ->default(now())
                                ->required(),
                            // 4. CODICI ANOMALIA MULTIPLI
                            Select::make('anomalies_codes')
                                ->label('Codici Anomalia Rilevati')
                                ->multiple()
                                ->options([
                                    'AN01' => 'AN01 - Operazioni frazionate ingiustificate',
                                    'AN02' => 'AN02 - Utilizzo anomalo di contante',
                                    'AN03' => 'AN03 - Documentazione identificativa carente',
                                    'AN04' => 'AN04 - Comportamento reticente del cliente',
                                ])
                                ->placeholder('Seleziona uno o più codici')
                                ->columnSpanFull(),
                            // 5. DESCRIZIONE DETTAGLIATA
                            Textarea::make('description')
                                ->label("Descrizione dell'Attività Sospetta")
                                ->required()
                                ->rows(6)
                                ->placeholder("Fornisci una descrizione chiara e dettagliata dell'anomalia riscontrata..."),
                            Section::make('Stato & Logistica')
                                ->schema([
                                    // STATO DELLA SEGNALAZIONE
                                    Select::make('status')
                                        ->label('Stato Avanzamento')
                                        ->options([
                                            'pending' => 'In Attesa',
                                            'investigated' => 'In Investigazione',
                                            'reported' => 'Segnalata a UIF',
                                            'archived' => 'Archiviata',
                                        ])
                                        ->default('pending')
                                        ->required(),
                                ]),
                        ]),
                    ]),
            ]);
    }
}
