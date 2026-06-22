<?php

namespace App\Filament\Resources\CompanyInspections\Schemas;

use App\Models\Company;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanyInspectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Dati Ispezione')
                ->columns(2)
                ->components([
                    Select::make('company_id')
                        ->label('Azienda')
                        ->relationship('company', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->default(fn () => Company::first()?->id)
                        ->columnSpanFull(),

                    TextInput::make('name')
                        ->label('Nome / Descrizione ispezione')
                        ->required()
                        ->columnSpanFull(),

                    DatePicker::make('dal')
                        ->label('Data inizio')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->required(),

                    DatePicker::make('al')
                        ->label('Data fine')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->afterOrEqual('dal'),

                    Select::make('execution_method')
                        ->label('Metodo di esecuzione')
                        ->options([
                            'documentale' => 'Documentale',
                            'onsite' => 'In loco',
                            '' => 'Non specificato',
                        ])
                        ->default(''),

                    TextInput::make('ispectorName')
                        ->label('Nome ispettore'),

                    TextInput::make('n')
                        ->label('Numero progressivo')
                        ->numeric()
                        ->minValue(1),
                ]),
        ]);
    }
}
