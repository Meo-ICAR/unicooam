<?php

namespace App\Filament\Resources\Documents\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome / titolo'),
                DatePicker::make('emitted_at')
                    ->label('Data emissione')
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                DatePicker::make('expires_at')
                    ->label('Data scadenza')
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                Toggle::make('is_endMonth')
                    ->label('Scadenza a fine mese')
                    ->required(),
                TextInput::make('document_url')
                    ->label('URL documento')
                    ->url()
                    ->required()
                    ->default('default'),
                TextInput::make('docnumber')
                    ->label('Numero documento'),
                TextInput::make('spatie_collection')
                    ->label('Collection media')
                    ->required()
                    ->default('default'),
                TextInput::make('status')
                    ->label('Stato')
                    ->required()
                    ->default('uploaded'),
                TextInput::make('sync_status')
                    ->label('Stato sincronizzazione')
                    ->required()
                    ->default('local'),
                TextInput::make('source_app')
                    ->label('Applicazione origine')
                    ->required()
                    ->default('local'),
                TextInput::make('app_id')
                    ->label('ID applicazione esterna'),
                TextInput::make('app_drive_id')
                    ->label('ID drive cloud'),
                TextInput::make('app_etag')
                    ->label('ETag cloud'),
                Textarea::make('extracted_text')
                    ->label('Testo estratto (OCR)')
                    ->columnSpanFull(),
                TextInput::make('metadata')
                    ->label('Metadati (JSON)'),
                Textarea::make('ai_abstract')
                    ->label('Riassunto AI')
                    ->columnSpanFull(),
                TextInput::make('ai_confidence_score')
                    ->label('Affidabilità AI (%)')
                    ->numeric(),
                Toggle::make('is_template')
                    ->label('Modello')
                    ->required(),
                Toggle::make('is_signed')
                    ->label('Firmato')
                    ->required(),
                Toggle::make('is_unique')
                    ->label('Unico nella collection')
                    ->required(),
                TextInput::make('emitted_by')
                    ->label('Emesso da'),
                DateTimePicker::make('delivered_at')
                    ->label('Consegnato il'),
                DateTimePicker::make('signed_at')
                    ->label('Firmato il'),
                Textarea::make('description')
                    ->label('Descrizione')
                    ->columnSpanFull(),
                Textarea::make('internal_notes')
                    ->label('Note interne')
                    ->columnSpanFull(),
                Textarea::make('rejection_note')
                    ->label('Motivo rifiuto')
                    ->columnSpanFull(),
                Select::make('user_id')
                    ->label('Intestatario')
                    ->relationship('user', 'name'),
                TextInput::make('uploaded_by')
                    ->label('Caricato da (ID utente)')
                    ->numeric(),
                TextInput::make('verified_by')
                    ->label('Verificato da (ID utente)')
                    ->numeric(),
                DateTimePicker::make('verified_at')
                    ->label('Verificato il'),
                TextInput::make('created_by')
                    ->label('Creato da (ID utente)')
                    ->numeric(),
                TextInput::make('updated_by')
                    ->label('Aggiornato da (ID utente)')
                    ->numeric(),
                TextInput::make('deleted_by')
                    ->label('Eliminato da (ID utente)')
                    ->numeric(),
                TextInput::make('file_hash')
                    ->label('Hash file'),
                Select::make('company_id')
                    ->label('Azienda')
                    ->relationship('company', 'name'),
                TextInput::make('documentable_type')
                    ->label('Tipo entità collegata')
                    ->required(),
                TextInput::make('documentable_id')
                    ->label('ID entità collegata')
                    ->required(),
                Select::make('document_type_id')
                    ->label('Tipo documento')
                    ->relationship('documentType', 'name'),
            ]);
    }
}
