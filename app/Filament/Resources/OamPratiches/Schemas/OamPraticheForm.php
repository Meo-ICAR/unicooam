<?php

namespace App\Filament\Resources\OamPratiches\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OamPraticheForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                // 1. DATI ANAGRAFICI E IDENTIFICATIVI
                Section::make('Informazioni Generali Pratica')
                    ->description('Dettagli identificativi del prodotto creditizio e dei soggetti coinvolti')
                    ->icon('heroicon-o-document-text')
                    ->columnSpanFull() // <--- Prende il 100% della larghezza
                    ->columns([
                        'sm' => 1,
                        'md' => 3,
                    ])
                    ->schema([

                        TextInput::make('prodotto_creditizio')
                            ->label('Prodotto Creditizio')
                            ->required(),

                        TextInput::make('tipo_prodotto')
                            ->label('Tipo Prodotto'),

                        TextInput::make('abi_name')
                            ->label('Finanziatore / ABI'),

                        TextInput::make('istituto')
                            ->label('Istituto'),

                        TextInput::make('agente')
                            ->label('Agente'),

                        TextInput::make('cliente')
                            ->label('Cliente'),
                    ]),

                // 2. TIMELINE E DATE (Ora hanno tutto lo spazio orizzontale)
                Section::make('Stato e Cronologia Pratica')
                    ->icon('heroicon-o-calendar')
                    ->columnSpanFull() // <--- Prende il 100% della larghezza
                    ->columns([
                        'sm' => 1,
                        'md' => 6,
                    ])
                    ->schema([
                        TextInput::make('pratica')
                            ->label('Codice Pratica'),
                        DateTimePicker::make('sended_at')
                            ->label('Inviata il'),

                        DateTimePicker::make('approved_at')
                            ->label('Approvata il'),

                        DateTimePicker::make('erogated_at')
                            ->label('Erogata il'),

                        DateTimePicker::make('rejected_at')
                            ->label('Respinta il'),

                    ]),

                // 3. CONTEGGI E INTERMEDIARI
                Section::make('Volumi e Intermediari')
                    ->icon('heroicon-o-users')
                    ->columnSpanFull() // <--- Prende il 100% della larghezza
                    ->columns([
                        'sm' => 1,
                        'md' => 4,
                    ])
                    ->schema([
                        TextInput::make('pratiche_intermediate')
                            ->label('Pratiche Intermediate')
                            ->numeric()
                            ->default(0),

                        TextInput::make('pratiche_lavorazione')
                            ->label('Pratiche in Lavorazione')
                            ->numeric()
                            ->default(0),

                        TextInput::make('intermediari_convenzionati')
                            ->label('Intermediari Convenzionati')
                            ->numeric()
                            ->default(0),

                        TextInput::make('intermediari_non_convenzionati')
                            ->label('Intermediari Non Convenzionati')
                            ->numeric()
                            ->default(0),
                    ]),

                // 4. EROGATO E PROVVIGIONI BASE
                Section::make('Importi ed Erogato')
                    ->icon('heroicon-o-currency-euro')
                    ->columnSpanFull() // <--- Prende il 100% della larghezza
                    ->columns([
                        'sm' => 1,
                        'md' => 5,
                    ])
                    ->schema([
                        TextInput::make('erogato_lordo')
                            ->label('Erogato Lordo')
                            ->numeric()
                            ->prefix('€')
                            ->step(0.01)
                            ->default(0.00),

                        TextInput::make('erogato_lavorazione')
                            ->label('Erogato in Lavorazione')
                            ->numeric()
                            ->prefix('€')
                            ->step(0.01)
                            ->default(0.00),

                        TextInput::make('provv_clientela')
                            ->label('Provvigioni Clientela')
                            ->numeric()
                            ->prefix('€')
                            ->step(0.01)
                            ->default(0.00),

                        TextInput::make('provv_istituto_comp')
                            ->label('Provvigioni Istituto')
                            ->numeric()
                            ->prefix('€')
                            ->step(0.01)
                            ->default(0.00),

                        TextInput::make('premi_istituto_comp')
                            ->label('Premi Istituto')
                            ->numeric()
                            ->prefix('€')
                            ->step(0.01)
                            ->default(0.00),
                    ]),

                // 5. DETTAGLIO FLUSSI FINANZIARI (PAY-IN / PAY-OUT)
                Section::make('Dettaglio Flussi Finanziari')
                    ->icon('heroicon-o-arrows-right-left')
                    ->columnSpanFull() // <--- Prende il 100% della larghezza
                    ->collapsible()
                    ->schema([
                        Fieldset::make('Flussi Pay-In (Entrate)')
                            ->columns(3)
                            ->schema([
                                TextInput::make('payin_ass_banche')
                                    ->label('Ass. Banche')
                                    ->numeric()
                                    ->prefix('€')
                                    ->step(0.01)
                                    ->default(0.00),

                                TextInput::make('payin_ass_broker')
                                    ->label('Ass. Broker')
                                    ->numeric()
                                    ->prefix('€')
                                    ->step(0.01)
                                    ->default(0.00),

                                TextInput::make('payin_ass_broker_cap')
                                    ->label('Ass. Broker Captive')
                                    ->numeric()
                                    ->prefix('€')
                                    ->step(0.01)
                                    ->default(0.00),
                            ]),

                        Fieldset::make('Flussi Pay-Out (Uscite Rete)')
                            ->columns(4)
                            ->schema([
                                TextInput::make('payout_rete_credito')
                                    ->label('Rete Credito')
                                    ->numeric()
                                    ->prefix('€')
                                    ->step(0.01)
                                    ->default(0.00),

                                TextInput::make('payout_rete_ass_banche')
                                    ->label('Rete Ass. Banche')
                                    ->numeric()
                                    ->prefix('€')
                                    ->step(0.01)
                                    ->default(0.00),

                                TextInput::make('payout_rete_ass_broker')
                                    ->label('Rete Ass. Broker')
                                    ->numeric()
                                    ->prefix('€')
                                    ->step(0.01)
                                    ->default(0.00),

                                TextInput::make('payout_rete_ass_broker_cap')
                                    ->label('Rete Ass. Broker Captive')
                                    ->numeric()
                                    ->prefix('€')
                                    ->step(0.01)
                                    ->default(0.00),
                            ]),

                        Fieldset::make('Rivalse e Retrocessioni')
                            ->columns(3)
                            ->schema([
                                DateTimePicker::make('storned_at')
                                    ->label('Stornata il'),

                                TextInput::make('importo_retrocesse')
                                    ->label('Importo Stornato')
                                    ->numeric()
                                    ->prefix('€')
                                    ->step(0.01)
                                    ->default(0.00),

                                TextInput::make('num_rivalse')
                                    ->label('N. Rivalse')
                                    ->numeric()
                                    ->default(0),
                            ]),
                    ]),
            ]);
    }
}
