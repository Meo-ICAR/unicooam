<?php

namespace App\Filament\Resources\Audits\Schemas;

use App\Enums\AuditStatus;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PROFORMA\Fornitore;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\MorphToSelect\Type;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class AuditForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                // SEZIONE 1: Dati Base e Soggetti
                Section::make('Informazioni Principali')
                    ->description('Definisci il soggetto sotto controllo e il richiedente.')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        Grid::make()->schema([
                            // Gestione nativa e pulita del polimorfismo!
                            MorphToSelect::make('auditable')
                                ->label('Oggetto / Soggetto Controllato')
                                ->types([
                                    Type::make(Fornitore::class)
                                        ->titleAttribute('name')
                                        //     ->modifyOptionsQueryUsing(fn(Builder $query) => $query->whereNull($this->dismissed_at))
                                        ->label('Produttore'),
                                    Type::make(Employee::class)
                                        ->titleAttribute('name')  // o 'full_name'
                                        ->label('Dipendente'),
                                    Type::make(Company::class)
                                        ->titleAttribute('name')
                                        ->label('Azienda'),
                                    Type::make(Branch::class)
                                        ->titleAttribute('name')
                                        ->label('Sede'),
                                ])
                                ->searchable()
                                ->preload()
                                ->required(),
                        ])->columnSpanFull(),
                        Select::make('collaboratore')
                            ->label('collaboratore')
                            ->options([
                                'dipendente/collaboratore' => 'Dipendente / Collaboratore',
                                'dipendente/collaboratore supervisore' => 'Dipendente / Collaboratore Supervisore',
                                'dipendente/collaboratore filiale' => 'Dipendente / Collaboratore Filiale',
                                'altra u.o.' => 'Altra U.O.',
                                'Ufficio XXX' => 'Ufficio XXX - (N. dipendenti/collaboratori)',
                            ])
                            ->required()
                            ->default('dipendente/collaboratore'),
                    ]),
                Grid::make()->schema([
                    Select::make('origin_type')
                        ->label('Origine Audit')
                        ->options([
                            'internal' => 'Audit Interno (Routine)',
                            'external_incoming' => 'Ispezione da Ente Esterno',
                            'whistleblowing' => 'Segnalazione / Whistleblowing',
                        ])
                        ->required()
                        ->default('internal')
                        ->live(),  // <--- FONDAMENTALE: rende il campo reattivo al cambio di selezione
                    Select::make('organization_id')
                        ->label('Richiedente')
                        ->relationship('organization', 'name')
                        ->searchable()
                        ->preload()
                        ->placeholder('Seleziona richiedente')
                        // <--- SEGRETO DI FILAMENT: si nasconde se origin_type è 'internal'
                        ->hidden(fn (Get $get) => $get('origin_type') === 'internal')
                        // Opzionale: lo rende obbligatorio solo se l'ispezione è esterna
                        ->required(fn (Get $get) => $get('origin_type') === 'external_incoming'),
                ])->columnSpanFull(),

                // SEZIONE 2: Pianificazione e Stato
                Section::make('Esecuzione e Stato')
                    ->description('Dettagli operativi, date e stato di avanzamento.')
                    ->icon('heroicon-o-calendar')
                    ->schema([
                        Grid::make(3)->schema([
                            DatePicker::make('scheduled_at')
                                ->label('Data Pianificata')
                                ->displayFormat('d/m/y')
                                ->native(false),
                            DatePicker::make('executed_at')
                                ->label('Data Esecuzione')
                                ->displayFormat('d/m/y')
                                ->native(false),
                            TextInput::make('protocol_number')
                                ->label('Numero di Protocollo')
                                ->maxLength(255)
                                ->placeholder('Es. OAM-2026/1234'),
                            ToggleButtons::make('status')
                                ->label('Stato Audit')
                                ->options(AuditStatus::class)
                                ->default(AuditStatus::PLANNED)
                                ->inline()
                                ->required(),
                            TextInput::make('auditor_name')
                                ->label('Auditor')
                                ->maxLength(255),
                            Select::make('execution_method')
                                ->label('Metodo di Esecuzione')
                                ->options([
                                    'documentale' => 'Documentale / Da Remoto',
                                    'onsite' => 'On Site (Ispezione in filiale)',
                                    'intervista' => 'Intervista',
                                ])
                                ->required()
                                ->default('documentale'),
                        ]),
                    ]),
                // SEZIONE 3: Esiti e Reportistica
                Section::make('Report e Compliance')
                    ->description("Esito dell'ispezione e documentazione dei rilievi.")
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Textarea::make('scope')
                            ->label('Ambito del Controllo')
                            ->placeholder('Es. Verifica pratiche antiriciclaggio Q1 2026...')
                            ->rows(2)
                            ->columnSpanFull(),

                        Select::make('rilievi_codes')
                            ->label('Codici Rilievi')
                            ->multiple()
                            ->options([
                                '1A' => '1A - contatto con soggetti non iscritti - Rilievo afferente all’attività a contatto con il pubblico;',
                                '1B' => '1B - utilizzo materiale pubblicitario non adeguato - Rilievo afferente all’attività a contatto con il pubblico;',
                                '1C' => '1C - utilizzo sito  web non autorizzato - Rilievo afferente all’attività a contatto con il pubblico;',
                                '1D' => '1D - inosservanza delle policies aziendali in materia - Rilievo afferente all’attività a contatto con il pubblico;',
                                '2A' => '2A - documentazione informativa e di trasparenza  - Rilievo afferente all’attività di promozione e conclusione di finanziamento',
                                '2B' => '2B - carenza generalizzata di informazioni presso la sede/uffici - Rilievo afferente all’attività di promozione e conclusione di finanziamento',
                                '2C' => '2C - consultazione delle informazioni creditizie del cliente in assenza di consenso dello stesso al fine di vincolarlo dal punto di vista commerciale - Rilievo afferente all’attività di promozione e conclusione di finanziamento',
                                '2D' => '2D - incompleta/parziale rappresentazione alla clientela delle caratteristiche del  servizio di mediazione creditizia - Rilievo afferente all’attività di promozione e conclusione di finanziamento',
                                '3A' => '3A - omessa consegna al cliente della documentazione precontrattuale e, ove richiesto, dello schema di contratto relativi al rapporto di mediazione, in tempo utile per consentire un consapevole e informato conferimento dell incarico (inosservanza delle procedure aziendali)  - Rilievo afferente all’utilizzo degli strumenti informaticidell’intermediario',
                                '3B' => '3B - incompleta/imparziale compilazione della modulistica afferente al servizio di mediazione creditizia - Rilievo afferente all’utilizzo degli strumenti informatici dell’intermediario',
                                '3C' => '3C - incompleta identificazione e verifica dei clienti/titolare effettivo/coobligati - alterazione documenazione acquisita - Rilievo afferente all utilizzo degli strumenti informatici dell intermediario',
                                '3D' => '3D - mancato/parziale utilizzo degli strumenti aziendali finalizzati alla gestione/trasmissione della documentazione alla societa - Rilievo afferente all’utilizzo degli strumenti informatici dell’intermediario',
                                'Altro' => 'Altro',
                            ])
                            ->default('Altro')
                            ->placeholder('Seleziona uno o più codici')
                            ->columnSpanFull(),

                        Select::make('severity')
                            ->label('Severity')
                            ->options([
                                'Alto' => 'Alto',
                                'Medio' => 'Medio',
                                'Basso' => 'Basso',
                            ]),
                        Select::make('outcome')
                            ->label('Esito Finale')
                            ->options([
                                'passato' => 'Passato / Conforme',
                                'con_rilievi' => 'Con Rilievi (Non conformità minori)',
                                'fallito' => 'Fallito / Grave non conformità',
                            ])
                            ->native(false),
                        Textarea::make('summary')
                            ->label('Sintesi dei Risultati')
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('auditor_notes')
                            ->label('Note Riservate Auditor')
                            ->helperText('Queste note sono visibili solo al team compliance.')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
                // SEZIONE 4: Piano di Rimedio (Remediation Plan)
                Section::make('Risoluzione Anomalie (Remediation)')
                    ->description('Da compilare se sono emerse non conformità.')
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->collapsed()  // Nasconde la sezione di default per tenere l'interfaccia pulita
                    ->schema([
                        Textarea::make('remediation_plan')
                            ->label('Piano di Rientro Richiesto')
                            ->placeholder('Azioni che il collaboratore deve intraprendere per sanare le anomalie...')
                            ->rows(3)
                            ->columnSpanFull(),
                        DatePicker::make('followup_date')
                            ->label('Data Scadenza Follow-up')
                            ->displayFormat('d/m/y')
                            ->native(false)
                            ->helperText('Data entro cui verificare che le anomalie siano state sanate.'),
                    ]),
            ]);
    }
}
