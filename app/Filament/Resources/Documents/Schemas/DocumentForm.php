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
                TextInput::make('name'),
                DatePicker::make('emitted_at'),
                DatePicker::make('expires_at'),
                Toggle::make('is_endMonth')
                    ->required(),
                TextInput::make('document_url')
                    ->url()
                    ->required()
                    ->default('default'),
                TextInput::make('docnumber'),
                TextInput::make('spatie_collection')
                    ->required()
                    ->default('default'),
                TextInput::make('status')
                    ->required()
                    ->default('uploaded'),
                TextInput::make('sync_status')
                    ->required()
                    ->default('local'),
                TextInput::make('source_app')
                    ->required()
                    ->default('local'),
                TextInput::make('app_id'),
                TextInput::make('app_drive_id'),
                TextInput::make('app_etag'),
                Textarea::make('extracted_text')
                    ->columnSpanFull(),
                TextInput::make('metadata'),
                Textarea::make('ai_abstract')
                    ->columnSpanFull(),
                TextInput::make('ai_confidence_score')
                    ->numeric(),
                Toggle::make('is_template')
                    ->required(),
                Toggle::make('is_signed')
                    ->required(),
                Toggle::make('is_unique')
                    ->required(),
                TextInput::make('emitted_by'),
                DateTimePicker::make('delivered_at'),
                DateTimePicker::make('signed_at'),
                Textarea::make('description')
                    ->columnSpanFull(),
                Textarea::make('internal_notes')
                    ->columnSpanFull(),
                Textarea::make('rejection_note')
                    ->columnSpanFull(),
                Select::make('user_id')
                    ->relationship('user', 'name'),
                TextInput::make('uploaded_by')
                    ->numeric(),
                TextInput::make('verified_by')
                    ->numeric(),
                DateTimePicker::make('verified_at'),
                TextInput::make('created_by')
                    ->numeric(),
                TextInput::make('updated_by')
                    ->numeric(),
                TextInput::make('deleted_by')
                    ->numeric(),
                TextInput::make('file_hash'),
                Select::make('company_id')
                    ->relationship('company', 'name'),
                TextInput::make('documentable_type')
                    ->required(),
                TextInput::make('documentable_id')
                    ->required(),
                Select::make('document_type_id')
                    ->relationship('documentType', 'name'),
            ]);
    }
}
