<?php

namespace App\Filament\Resources\DocumentTypes\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DocumentTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Regole di Attivazione Dinamica')
                    ->description('Configura le condizioni per cui questo task deve attivarsi in base ai dati del record.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome Documento')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn(string $operation, $state, $set) =>
                                $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                        Textarea::make('description')
                            ->label('Descrizione Aggiuntiva')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextColumn::make('document_url')
                            ->label('URL')
                            ->url(fn($record) => $record->document_url)
                            ->searchable()
                            ->sortable(),
                    ]),
                Section::make('File Allegato')
                    ->components([
                        SpatieMediaLibraryFileUpload::make('attachments')
                            ->label('Carica file (PDF, immagini, Word)')
                            ->multiple()
                            ->collection('documents')
                            ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                            ->maxSize(20480)
                            ->columnSpanFull(),
                    ]),
                // TAB 2: DESTINATARI E FLUSSO
                Section::make('Soggetti Interessati')
                    ->description('Indica a quali entità o ruoli si applica questo documento')
                    ->columns(4)
                    ->schema([
                        Toggle::make('is_person')->label('Persona')->default(true)->inline(false),
                        Toggle::make('is_company')->label('Azienda')->default(false)->inline(false),
                        Toggle::make('is_employee')->label('Dipendente')->default(false)->inline(false),
                        Toggle::make('is_agent')->label('Agente')->default(false)->inline(false),
                        Toggle::make('is_principal')->label('Mandante')->default(false)->inline(false),
                        Toggle::make('is_client')->label('Cliente')->default(false)->inline(false),
                        Toggle::make('is_practice')->label('Legato a Pratica')->default(false)->inline(false),
                        Select::make('nature')
                            ->label('Tipologia invio')
                            ->options([
                                'incoming' => 'Ricevuto',
                                'template_fillable' => 'Compilazione ns. Template',
                                'outgoing' => 'Inviato',
                                //  'compliance' => 'Compliance',
                            ])
                            ->default('incoming'),
                        Toggle::make('is_signed')->label('Check sia Firmato')->inline(false),
                        Toggle::make('is_sensible')->label('Contiene Dati Sensibili')->inline(false),
                        TextInput::make('slug')
                            ->label('Codice documento ')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2)
                            ->disabled()
                            ->unique(ignoreRecord: true),
                    ]),
                Section::make('Caratteristiche scadenza e rinnovo')
                    ->columnSpanFull()
                    ->columns(5)
                    ->schema([
                        Toggle::make('is_monitored')
                            ->label('Monitora Scadenza')
                            ->live()
                            ->inline(false),
                        TextInput::make('duration')
                            ->label('Durata Validità')
                            ->numeric()
                            ->visible(fn($get) => $get('is_monitored')),
                        Select::make('duration_unit')
                            ->label('Unità di Misura')
                            ->options([
                                'days' => 'Giorni',
                                'months' => 'Mesi',
                                'years' => 'Anni',
                            ])
                            ->default('days')
                            ->visible(fn($get) => $get('is_monitored')),
                        Toggle::make('is_endMonth')
                            ->label('Approssima a Fine Mese')
                            ->visible(fn($get) => $get('is_monitored')),
                        Select::make('renewed_by_id')
                            ->label('Rinnovato da altro documento')
                            ->relationship('renewedBy', 'name')
                            ->searchable()
                            ->visible(fn($get) => $get('is_monitored'))
                            ->placeholder('Seleziona tipo...'),
                    ]),
            ]);
    }
}
