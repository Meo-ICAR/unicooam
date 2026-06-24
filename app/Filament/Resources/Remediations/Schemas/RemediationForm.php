<?php

namespace App\Filament\Resources\Remediations\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class RemediationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('remediation_type')
                    ->options([
            'AML' => 'A m l',
            'Gestione Reclami' => 'Gestione reclami',
            'Monitoraggio Rete' => 'Monitoraggio rete',
            'Privacy' => 'Privacy',
            'Trasparenza' => 'Trasparenza',
            'Assetto Organizzativo' => 'Assetto organizzativo',
        ]),
                TextInput::make('name')
                    ->required(),
                TextInput::make('code'),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('timeframe_hours')
                    ->numeric(),
                TextInput::make('timeframe_desc'),
            ]);
    }
}
