<?php

namespace App\Filament\Unicofin\Resources\Praticas\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PraticaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // ID nascosto se generato automaticamente (UUID)
                Hidden::make('id'),

                // 1. INFORMAZIONI GENERALI
                Section::make('Informazioni Generali e Stato')
                    ->description('Dati identificativi della pratica')
                    ->schema([
                        TextInput::make('codice_pratica')
                            ->label('Codice Pratica')
                            ->maxLength(255)
                            ->required(),

                        Select::make('stato_pratica')
                            ->label('Stato Pratica')
                            ->options([
                                'inserita' => 'Inserita',
                                'istruttoria' => 'In Istruttoria',
                                'deliberata' => 'Deliberata / Approvata',
                                'erogata' => 'Erogata',
                                'respinta' => 'Respinta / Rifiutata',
                                'rinunciata' => 'Rinunciata',
                            ])
                            ->searchable()
                            ->required(),

                        DatePicker::make('data_inserimento_pratica')
                            ->label('Data Inserimento')
                            ->default(now()),

                        Toggle::make('is_notowned')
                            ->label('Pratica di terzi (Not Owned)')
                            ->inline(false)
                            ->default(false),
                    ])->columns(4),

                // 2. ANAGRAFICA CLIENTE
                Section::make('Anagrafica Cliente')
                    ->schema([
                        TextInput::make('nome_cliente')
                            ->label('Nome')
                            ->maxLength(191)
                            ->required(),

                        TextInput::make('cognome_cliente')
                            ->label('Cognome')
                            ->maxLength(191)
                            ->required(),

                        TextInput::make('codice_fiscale')
                            ->label('Codice Fiscale')
                            ->length(16)
                            ->regex('/^[A-Za-z]{6}[0-9LMNPQRSTUV]{2}[A-Za-z][0-9LMNPQRSTUV]{2}[A-Za-z][0-9LMNPQRSTUV]{3}[A-Za-z]$/i')
                            ->validationMessages([
                                'regex' => 'Il formato del Codice Fiscale non è valido.',
                            ])
                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                            ->maxLength(191)
                            ->required(),
                    ])->columns(3),

                // 3. AGENTE E ISTITUTO BANCARIO
                Section::make('Dati Rete e Istituto')
                    ->schema([
                        TextInput::make('denominazione_agente')
                            ->label('Agente / Rappresentante')
                            ->maxLength(191),

                        TextInput::make('partita_iva_agente')
                            ->label('Partita IVA Agente')
                            ->numeric()
                            ->length(11)
                            ->maxLength(20),

                        TextInput::make('denominazione_banca')
                            ->label('Banca Erogatrice')
                            ->maxLength(191),

                        Grid::make(2)->schema([
                            TextInput::make('abi')
                                ->label('Codice ABI')
                                ->numeric()
                                ->length(5)
                                ->maxLength(20),

                            TextInput::make('abi_name')
                                ->label('Nome Filiale/ABI')
                                ->maxLength(255),
                        ]),
                    ])->columns(2),

                // 4. DATI PRODOTTO E FINANZIARI
                Section::make('Dati Prodotto e Importi')
                    ->schema([
                        TextInput::make('tipo_prodotto')
                            ->label('Macro Prodotto (es. CQS, Mutuo)')
                            ->maxLength(191),

                        TextInput::make('denominazione_prodotto')
                            ->label('Prodotto Specifico')
                            ->maxLength(191),

                        Grid::make(5)->schema([
                            TextInput::make('amount')
                                ->label('Importo Lordo')
                                ->numeric()
                                ->inputMode('decimal')
                                ->prefix('€'),

                            TextInput::make('net')
                                ->label('Importo Netto')
                                ->numeric()
                                ->inputMode('decimal')
                                ->prefix('€'),

                            TextInput::make('erogato')
                                ->label('Erogato')
                                ->numeric()
                                ->inputMode('decimal')
                                ->prefix('€'),

                            TextInput::make('rata')
                                ->label('Rata Mensile')
                                ->numeric()
                                ->inputMode('decimal')
                                ->prefix('€'),

                            TextInput::make('nrate')
                                ->label('N. Rate')
                                ->numeric()
                                ->integer()
                                ->minValue(1)
                                ->maxValue(480), // es. limite massimo per mutui a 40 anni
                        ]),
                    ])->columns(2),

                // 5. TIMELINE OPERATIVA
                Section::make('Timeline Operativa')
                    ->description('Date di avanzamento della pratica')
                    ->schema([
                        DatePicker::make('sended_at')
                            ->label('Data Invio a Banca'),

                        DatePicker::make('approved_at')
                            ->label('Data Approvazione'),

                        DatePicker::make('erogated_at')
                            ->label('Data Erogazione'),

                        DatePicker::make('rejected_at')
                            ->label('Data Rifiuto'),

                        DateTimePicker::make('upload_at')
                            ->label('Data e Ora Upload Documentale')
                            ->default(now())
                            ->required(),
                    ])->columns(5),
            ]);

    }
}
