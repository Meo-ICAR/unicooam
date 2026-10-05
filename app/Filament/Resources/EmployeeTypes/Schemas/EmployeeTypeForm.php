<?php

namespace App\Filament\Resources\EmployeeTypes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class EmployeeTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name'),
                TextInput::make('icon'),
                TextInput::make('companytype'),
                Toggle::make('is_external'),
            ]);
    }
}
