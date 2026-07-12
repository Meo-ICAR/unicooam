<?php

namespace App\Filament\Unicofin\Resources\Employees\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name'),
                Select::make('user_id')
                    ->relationship('user', 'name'),
                Select::make('branch_id')
                    ->relationship('branch', 'name'),
                TextInput::make('name'),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('role_title'),
                TextInput::make('cf'),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('pec'),
                TextInput::make('phone')
                    ->tel(),
                TextInput::make('department'),
                TextInput::make('oam'),
                DatePicker::make('oam_at'),
                TextInput::make('oam_name'),
                TextInput::make('numero_iscrizione_rui'),
                DatePicker::make('oam_dismissed_at'),
                TextInput::make('ivass'),
                DatePicker::make('hiring_date'),
                DatePicker::make('termination_date'),
                TextInput::make('coordinated_by_id')
                    ->numeric(),
                TextInput::make('employee_types')
                    ->required()
                    ->default('dipendente'),
                TextInput::make('supervisor_type')
                    ->required()
                    ->default('no'),
                TextInput::make('privacy_role'),
                Textarea::make('purpose')
                    ->columnSpanFull(),
                Textarea::make('data_subjects')
                    ->columnSpanFull(),
                Textarea::make('data_categories')
                    ->columnSpanFull(),
                TextInput::make('retention_period'),
                TextInput::make('extra_eu_transfer'),
                Textarea::make('security_measures')
                    ->columnSpanFull(),
                TextInput::make('privacy_data'),
                Toggle::make('is_structure')
                    ->required(),
                Toggle::make('is_ghost')
                    ->required(),
                TextInput::make('created_by')
                    ->numeric(),
                TextInput::make('updated_by')
                    ->numeric(),
                TextInput::make('deleted_by')
                    ->numeric(),
            ]);
    }
}
