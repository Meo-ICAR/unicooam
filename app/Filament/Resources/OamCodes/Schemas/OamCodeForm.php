<?php

namespace App\Filament\Resources\OamCodes\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OamCodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Codice OAM'),
                TextInput::make('name')
                    ->label('Nome'),
                TextInput::make('description')
                    ->label('Descrizione'),
                TextInput::make('tipo_prodotto')
                    ->label('Tipo prodotto'),
                Section::make('Associazione mandanti')
                    ->description('Seleziona i mandanti convenzionati a cui associare questo codice OAM.')
                    ->schema([
                        CheckboxList::make('clienti')
                            ->label('Mandanti')
                            ->relationship(
                                name: 'clienti',
                                titleAttribute: 'name'
                            )
                            ->searchable()
                            ->bulkToggleable()
                            ->columns(3)
                            ->gridDirection('row'),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
