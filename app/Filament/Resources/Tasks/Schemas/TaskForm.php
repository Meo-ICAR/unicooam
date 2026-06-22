<?php

namespace App\Filament\Resources\Tasks\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome attività'),
                TextInput::make('description')
                    ->label('Descrizione'),
                Section::make('Associazione documenti')
                    ->description('Seleziona i tipi documento da associare all\'attività.')
                    ->schema([
                        CheckboxList::make('documentTypes')
                            ->label('Tipi documento')
                            ->relationship(
                                name: 'documentTypes',
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
