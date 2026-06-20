<?php

namespace App\Filament\Resources\DocumentTypes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DocumentTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name'),
                TextInput::make('description'),
                TextInput::make('code'),
                TextInput::make('codegroup'),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('regex_pattern'),
                TextInput::make('priority')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('phase'),
                Toggle::make('is_person')
                    ->required(),
                Toggle::make('is_company')
                    ->required(),
                Toggle::make('is_employee')
                    ->required(),
                Toggle::make('is_agent')
                    ->required(),
                Toggle::make('is_principal')
                    ->required(),
                Toggle::make('is_client')
                    ->required(),
                Toggle::make('is_practice')
                    ->required(),
                Toggle::make('is_signed')
                    ->required(),
                Toggle::make('is_monitored')
                    ->required(),
                TextInput::make('duration')
                    ->numeric(),
                TextInput::make('emitted_by'),
                Toggle::make('is_sensible')
                    ->required(),
                Toggle::make('is_template')
                    ->required(),
                Toggle::make('is_stored')
                    ->required(),
                TextInput::make('regex'),
                Toggle::make('is_endmonth')
                    ->required(),
                Toggle::make('is_AiAbstract')
                    ->required(),
                Toggle::make('is_AiCheck')
                    ->required(),
                Textarea::make('AiPattern')
                    ->columnSpanFull(),
                TextInput::make('min_confidence')
                    ->required()
                    ->numeric()
                    ->default(70),
                Toggle::make('allow_auto_verification')
                    ->required(),
                TextInput::make('notify_days_before'),
                TextInput::make('retention_years')
                    ->numeric(),
                TextInput::make('created_by')
                    ->numeric(),
                TextInput::make('updated_by')
                    ->numeric(),
                TextInput::make('deleted_by')
                    ->numeric(),
            ]);
    }
}
