<?php

namespace App\Filament\Unicofin\Resources\Branches\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BranchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('address'),
                TextInput::make('street_number'),
                TextInput::make('city'),
                TextInput::make('zip_code'),
                TextInput::make('province'),
                TextInput::make('region'),
                TextInput::make('branchable_type')
                    ->required(),
                TextInput::make('branchable_id')
                    ->required(),
                Toggle::make('is_main_office')
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('manager_first_name'),
                TextInput::make('manager_last_name'),
                TextInput::make('manager_tax_code'),
                DatePicker::make('founded_at'),
                DatePicker::make('dismissed_at'),
            ]);
    }
}
