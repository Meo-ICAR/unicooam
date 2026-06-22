<?php

namespace App\Filament\Resources\SuspiciousActivityReports\Schemas;

use App\Enums\AmlReportStatus;
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
                    ->label('Tipo segnalatore')
                    ->required(),
                TextInput::make('reporter_id')
                    ->label('ID segnalatore')
                    ->required()
                    ->numeric(),
                TextInput::make('anomalies_codes')
                    ->label('Codici anomalie'),
                Textarea::make('description')
                    ->label('Descrizione')
                    ->required()
                    ->columnSpanFull(),
                Select::make('status')
                    ->label('Stato')
                    ->options(AmlReportStatus::class)
                    ->default(AmlReportStatus::DRAFTED)
                    ->required(),
                DateTimePicker::make('reported_at')
                    ->label('Data segnalazione')
                    ->native(false)
                    ->displayFormat('d/m/Y H:i'),
            ]);
    }
}
