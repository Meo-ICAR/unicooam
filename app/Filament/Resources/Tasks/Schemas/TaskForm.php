<?php

namespace App\Filament\Resources\Tasks\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;

class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name'),
                TextInput::make('description'),
                Section::make('Associazione Documenti')
                    ->description('Spunta i documenti da  associare al task.')
                    ->schema([
                        CheckboxList::make('documentTypes')
                            ->relationship(
                                name: 'documentTypes',
                                titleAttribute: 'name'  // Assicurati che nel model Cliente ci sia la colonna 'name'
                            )
                            ->searchable()  // Permette di cercare tra i clienti se sono tanti
                            ->bulkToggleable()  // Aggiunge i pulsanti per selezionare/deselezionare tutto
                            ->columns(3)  // Mette le spunte su 3 colonne
                            ->gridDirection('row')
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
