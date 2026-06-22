<?php

namespace App\Filament\Resources\EmailTemplates\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class EmailTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Codice')
                    ->required(),
                TextInput::make('name')
                    ->label('Nome')
                    ->required(),
                TextInput::make('subject')
                    ->label('Oggetto')
                    ->required(),
                Textarea::make('body')
                    ->label('Corpo email')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('placeholders')
                    ->label('Segnaposto disponibili'),
                Toggle::make('is_active')
                    ->label('Attivo')
                    ->required(),
            ]);
    }
}
