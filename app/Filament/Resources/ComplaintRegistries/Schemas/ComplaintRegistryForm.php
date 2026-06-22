<?php

namespace App\Filament\Resources\ComplaintRegistries\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ComplaintRegistryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // SEZIONE 1: PROTOCOLLO E RICEZIONE
                Section::make('Inquadramento e Ricezione')
                    ->description('Dati di protocollo e modalità di ingresso della segnalazione')
                    ->collapsible()
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('protocol_number')
                                ->label('Numero Protocollo')
                                ->placeholder('Es. REC-2026-001')
                                ->required(),
                            //  ->unique(ignoringRecord: true),
                            DatePicker::make('received_at')
                                ->label('Data Ricezione')
                                ->default(now())
                                ->required(),
                            Select::make('reception_channel')
                                ->label('Canale di Ricezione')
                                ->options([
                                    'pec' => 'Posta Elettronica Certificata (PEC)',
                                    'raccomandata' => 'Raccomandata A/R',
                                    'email' => 'Email Ordinaria',
                                    'brevi_manu' => 'Consegna a Mano (Brevi Manu)',
                                ])
                                ->required(),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('receiving_email')
                                ->label('Email Aziendale Ricevente')
                                ->email()
                                ->placeholder('es. compliance@azienda.it')
                                ->maxLength(255),
                            TextInput::make('complainant_name')
                                ->label('Nome/Ragione Sociale Reclamante')
                                ->placeholder('Es. Mario Rossi o Rossi S.r.l.')
                                ->required(),
                        ]),
                        TextInput::make('complainant_email')
                            ->label('Email del Reclamante')
                            ->email()
                            ->placeholder('es. cliente@email.com'),
                    ]),
                // SEZIONE 2: NATURA DEL RECLAMO E OGGETTI COINVOLTI
                Section::make('Dettaglio Contestazione e Responsabilità')
                    ->description('Classificazione del reclamo e soggetti della rete coinvolti')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('macro_category')
                                ->label('Macro Ambito')
                                ->options([
                                    'financial' => 'Intermediazione Creditizia / Finanziario',
                                    'privacy' => 'Privacy e GDPR',
                                    'insurance' => 'Comparto Assicurativo (IVASS)',
                                    'operational' => 'Operativo / Servizi Generali',
                                ])
                                ->required(),
                            Select::make('category')
                                ->label('Categoria Specifica')
                                ->options([
                                    'delay' => 'Ritardi nella lavorazione/erogazione',
                                    'behavior' => "Comportamento scorretto dell'agente/collaboratore",
                                    'fraud' => 'Sospetta frode o falsificazione documentale',
                                    'rates' => 'Contestazione tassi / condizioni economiche',
                                    'gdpr_access' => 'Richiesta di Accesso ai Dati (Art. 15 GDPR)',
                                    'gdpr_erasure' => 'Richiesta di Cancellazione / Oblio (Art. 17 GDPR)',
                                    'transparency' => 'Mancanza di Trasparenza Informativa',
                                ])
                                ->required(),
                        ]),
                        Grid::make(2)->schema([
                            Select::make('agent_id')
                                ->label('Agente / Collaboratore Coinvolto')
                                ->relationship('agent', 'name')  // Assicurati che corrisponda alla colonna sul DB
                                ->searchable()
                                ->preload()
                                ->nullable(),
                            Select::make('bank_id')
                                ->label('Banca Mandante Coinvolta')
                                ->relationship('bank', 'name')  // Assicurati che corrisponda alla colonna sul DB
                                ->searchable()
                                ->preload()
                                ->nullable(),
                        ]),
                        Textarea::make('description')
                            ->label('Descrizione Dettagliata del Reclamo')
                            ->placeholder('Inserire qui i motivi della contestazione del cliente...')
                            ->required()
                            ->rows(5)
                            ->columnSpanFull(),
                        TextInput::make('financial_impact')
                            ->label('Impatto Economico Presunto (€)')
                            ->numeric()
                            ->prefix('€')
                            ->default(0.0),
                    ]),
                // SEZIONE 3: ISTRUTTORIA, SCADENZE E RISOLUZIONE
                Section::make('Flusso di lavoro e termini di legge')
                    ->description("Gestione dei tempi di risposta e note di chiusura dell'istruttoria")
                    ->collapsible()
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('status')
                                ->label('Stato Avanzamento')
                                ->options([
                                    'open' => 'Aperto / Ricevuto',
                                    'investigating' => 'In Istruttoria',
                                    'accepted' => 'Accolto (Chiuso)',
                                    'rejected' => 'Respinto (Chiuso)',
                                    'escalated' => 'In Escalation (ABF/Autorità)',
                                ])
                                ->default('open')
                                ->required(),
                            DatePicker::make('deadline_at')
                                ->label('Scadenza Risposta')
                                ->required()
                                ->hint("Termine tassativo per l'invio del riscontro"),
                            Toggle::make('is_extended')
                                ->label('Proroga Termini')
                                ->inline(false)
                                ->hint('Se i termini legali sono stati estesi'),
                        ]),
                        Grid::make(2)->schema([
                            DateTimePicker::make('resolved_at')
                                ->label('Data e Ora Risoluzione'),
                            Select::make('escalated_to')
                                ->label('Escalation / Ricorso')
                                ->options([
                                    'abf' => 'Arbitro Bancario Finanziario (ABF)',
                                    'oam' => 'Organismo Agenti e Mediatori (OAM)',
                                    'ivass' => 'IVASS',
                                    'garante' => 'Garante Privacy',
                                ])
                                ->nullable(),
                        ]),
                        Textarea::make('resolution_notes')
                            ->label("Esito dell'Istruttoria / Note di Risoluzione")
                            ->placeholder("Specificare le motivazioni dell'accoglimento o del rigetto...")
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
