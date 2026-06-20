<?php

namespace App\Filament\Resources\OamCodes\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;

class OamCodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code'),
                TextInput::make('name'),
                TextInput::make('description'),
                TextInput::make('tipo_prodotto'),
                // Seconda Sezione: Associazione rapida Clienti tramite Checkbox
                Section::make('Associazione Clienti')
                    ->description('Spunta mandanti convenzionati a cui associare questo codice OAM.')
                    ->schema([
                        CheckboxList::make('clienti')
                            ->relationship(
                                name: 'clienti',
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
