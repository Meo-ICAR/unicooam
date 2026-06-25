<?php

namespace App\Filament\Resources\DocumentTypes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DocumentTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome'),
                TextInput::make('description')
                    ->label('Descrizione'),
                TextInput::make('code')
                    ->label('Codice'),
                TextInput::make('codegroup')
                    ->label('Gruppo codice'),
                TextInput::make('slug')
                    ->label('Slug')
                    ->required(),
                TextInput::make('regex_pattern')
                    ->label('Pattern regex'),
                TextInput::make('priority')
                    ->label('Priorità')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('phase')
                    ->label('Fase'),
                Toggle::make('is_person')
                    ->label('Persona fisica')
                    ->required(),
                Toggle::make('is_company')
                    ->label('Azienda')
                    ->required(),
                Toggle::make('is_employee')
                    ->label('Dipendente')
                    ->required(),
                Toggle::make('is_agent')
                    ->label('Agente')
                    ->required(),
                Toggle::make('is_principal')
                    ->label('Mandante')
                    ->required(),
                Toggle::make('is_client')
                    ->label('Cliente')
                    ->required(),
                Toggle::make('is_practice')
                    ->label('Pratica')
                    ->required(),
                Toggle::make('is_signed')
                    ->label('Richiede firma')
                    ->required(),
                Toggle::make('is_monitored')
                    ->label('Monitorato')
                    ->required(),
                TextInput::make('duration')
                    ->label('Durata (giorni)')
                    ->numeric(),
                Select::make('renewed_by_id')
                    ->label('Rinnovato da')
                    ->relationship('renewedBy', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                // ->helperText('Seleziona un coordinatore della stessa sede'),
                TextInput::make('emitted_by')
                    ->label('Emesso da'),
                Toggle::make('is_sensible')
                    ->label('Dati sensibili')
                    ->required(),
                Toggle::make('is_template')
                    ->label('Modello')
                    ->required(),
                Toggle::make('is_stored')
                    ->label('Archiviato')
                    ->required(),
                TextInput::make('regex')
                    ->label('Regex validazione'),
                Toggle::make('is_endmonth')
                    ->label('Scadenza a fine mese')
                    ->required(),
                Toggle::make('is_AiAbstract')
                    ->label('Riassunto AI')
                    ->required(),
                Toggle::make('is_AiCheck')
                    ->label('Controllo AI')
                    ->required(),
                Textarea::make('AiPattern')
                    ->label('Pattern AI')
                    ->columnSpanFull(),
                TextInput::make('min_confidence')
                    ->label('Affidabilità minima (%)')
                    ->required()
                    ->numeric()
                    ->default(70),
                Toggle::make('allow_auto_verification')
                    ->label('Verifica automatica')
                    ->required(),
                TextInput::make('notify_days_before')
                    ->label('Giorni preavviso scadenza'),
                TextInput::make('retention_years')
                    ->label('Anni conservazione')
                    ->numeric(),
                TextInput::make('created_by')
                    ->label('Creato da (ID utente)')
                    ->numeric(),
                TextInput::make('updated_by')
                    ->label('Aggiornato da (ID utente)')
                    ->numeric(),
                TextInput::make('deleted_by')
                    ->label('Eliminato da (ID utente)')
                    ->numeric(),
            ]);
    }
}
