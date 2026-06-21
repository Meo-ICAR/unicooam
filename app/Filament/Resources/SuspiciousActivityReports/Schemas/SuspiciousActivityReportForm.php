<?php

namespace App\Filament\Resources\SuspiciousActivityReports\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SuspiciousActivityReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
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
