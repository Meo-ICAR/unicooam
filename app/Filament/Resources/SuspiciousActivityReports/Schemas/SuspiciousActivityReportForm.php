<?php

namespace App\Filament\Resources\COMPILANCE\SuspiciousActivityReports\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class SuspiciousActivityReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->required(),
                Select::make('client_id')
                    ->relationship('client', 'name'),
                TextInput::make('reporter_type')
                    ->required(),
                TextInput::make('reporter_id')
                    ->required()
                    ->numeric(),
                TextInput::make('anomalies_codes'),
                Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                Select::make('status')
                    ->options([
            'pending' => 'Pending',
            'investigated' => 'Investigated',
            'reported' => 'Reported',
            'archived' => 'Archived',
        ])
                    ->default('pending')
                    ->required(),
                DateTimePicker::make('reported_at'),
            ]);
    }
}
