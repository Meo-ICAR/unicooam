<?php

namespace App\Filament\Resources\Audits\Schemas;

use App\Enums\AuditStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class AuditForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // SEZIONE 1: ANAGRAFICA E TIPOLOGIA
                Section::make("Inquadramento dell'Audit")
                    ->description('Definisci la tipologia, il protocollo e il soggetto da controllare.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label("Titolo / Oggetto dell'Audit")
                            ->required()
                            ->placeholder('Es. Verifica Trasparenza Annuale 2026')
                            ->columnSpanFull(),
                        TextInput::make('protocol_number')
                            ->label('Numero Protocollo')
                            ->default(fn () => 'AUD-'.date('Y').'-'.strtoupper(Str::random(6)))
                            ->unique(ignoreRecord: true)
                            ->placeholder('Autogenerato se vuoto'),
                        Select::make('origin_type')
                            ->label('Direzione / Origine')
                            ->options([
                                'internal' => 'Interno (Sede Centrale)',
                                'incoming' => 'In Entrata (Subìto da Terzi/Autorità)',
                                'outgoing' => 'In Uscita (Effettuato verso la rete)',
                            ])
                            ->required()
                            ->live(),  // Rende il campo reattivo per mostrare/nascondere i campi autorità
                        // CAMPI CONDIZIONALI: Mostrati solo se l'audit è "In Entrata" (Incoming)
                        Grid::make(2)
                            ->schema([
                                Select::make('authority_type')
                                    ->label('Tipo Autorità / Richiedente')
                                    ->options([
                                        'oam' => 'OAM',
                                        'banca_italia' => "Banca d'Italia",
                                        'ivass' => 'IVASS',
                                        'garante' => 'Garante Privacy',
                                        'banca_mandante' => 'Banca Mandante',
                                        'other' => 'Altro',
                                    ])
                                    ->required(),
                                TextInput::make('authority_name')
                                    ->label('Nome Specifico Ente / Ispettore')
                                    ->placeholder('Es. Team Ispettivo OAM / Nome Banca'),
                            ])
                            //  ->visible(fn(Get $get) => $get('origin_type') === 'incoming')
                            ->columnSpanFull(),
                        Select::make('execution_method')
                            ->label('Modalità di Esecuzione')
                            ->options([
                                'documentale' => 'Documentale (Verifica da remoto)',
                                'ispezione' => 'Ispezione (In Loco presso sede/agente)',
                            ])
                            ->required()
                            ->default('documentale'),
                    ]),
                // SEZIONE 2: SOGGETTO POLIMORFICO (REATTIVO)
                Section::make('Soggetto Interessato')
                    ->description('Seleziona la natura del soggetto sottoposto a controllo.')
                    ->columns(2)
                    ->schema([
                        Select::make('auditable_type')
                            ->label('Natura del Soggetto')
                            ->options([
                                'App\Models\Azienda' => 'Sede Centrale / Azienda',
                                'App\Models\ReteCommerciale' => 'Rete Commerciale (Agenti/Collaboratori)',
                                'App\Models\BancaMandante' => 'Banca Mandante',
                                'App\Models\OrganismoVigilanza' => 'Autorità di Vigilanza',
                            ])
                            ->required()
                            ->live()
                            // Quando cambia il tipo, resetta l'ID del soggetto precedentemente selezionato
                            ->afterStateUpdated(fn (Set $set) => $set('auditable_id', null)),
                        Select::make('auditable_id')
                            ->label('Soggetto Specifico')
                            ->placeholder(fn (Get $get) => $get('auditable_type')
                                ? 'Seleziona dalla lista...'
                                : 'Scegli prima la Natura del Soggetto')
                            //    ->disabled(fn(Get $get) => !$get('auditable_type'))
                            ->options(function (Get $get) {
                                $type = $get('auditable_type');
                                if (! $type || ! class_exists($type)) {
                                    return [];
                                }

                                // Pluck dinamico dei dati in base al modello selezionato
                                return match ($type) {
                                    'App\Models\Company' => $type::pluck('name', 'id'),
                                    'App\Models\PROFORMA\Fornitore' => $type::pluck('name', 'id'),
                                    'App\Models\PROFORMA\Clienti' => $type::pluck('name', 'id'),
                                    //   'App\Models\OrganismoVigilanza' => $type::pluck('nome_organismo', 'id'),
                                    default => [],
                                };
                            })
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),
                // SEZIONE 3: STATO, ESITO E TEMPISTICHE
                Section::make('Stato e Scadenze')
                    ->columns(3)
                    ->schema([
                        Select::make('status')
                            ->label('Stato Avanzamento')
                            ->options(AuditStatus::class)  // Carica automaticamente le etichette dall'Enum PHP
                            ->required()
                            ->default(AuditStatus::Planned)
                            ->live(),
                        // L'esito ha senso solo se l'audit è in corso o completato
                        Select::make('outcome')
                            ->label('Esito Finale')
                            ->options([
                                'superato' => 'Superato (Nessun rilievo bloccante)',
                                'con_rilievi' => 'Superato con Rilievi',
                                'fallito' => 'Non Superato / Critico',
                            ])
                            ->disabled(fn (Get $get) => in_array($get('status'), [AuditStatus::Planned->value, null]))
                            ->placeholder('In attesa di esito'),
                        DatePicker::make('scheduled_at')
                            ->label('Data Pianificata')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                        DatePicker::make('executed_at')
                            ->label('Data Effettiva Esecuzione')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                        DatePicker::make('followup_date')
                            ->label('Data prevista verifica di follow-up')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                    ]),
                // SEZIONE 4: CONTENUTO ISPETTIVO E NOTE
                Section::make('Note e Perimetro del Controllo')
                    ->collapsible()
                    ->schema([
                        Textarea::make('scope')
                            ->label("Perimetro / Oggetto dell'Audit")
                            ->placeholder('Specificare i processi o i prodotti campionati (es. Cessione del Quinto, Trasparenza precontrattuale...)')
                            ->rows(3),
                        RichEditor::make('summary')
                            ->label("Sintesi dell'Audit (Verbale / Risultanze)")
                            ->toolbarButtons([
                                'blockquote', 'bold', 'bulletList', 'orderedList', 'redo', 'undo',
                            ])
                            ->columnSpanFull(),
                        Textarea::make('auditor_notes')
                            ->label("Note Interne dell'Auditor (Riservate)")
                            ->placeholder('Appunti interni del team di compliance non inseriti nel verbale ufficiale')
                            ->rows(3),
                    ]),
            ]);
    }
}
