<?php

namespace App\Filament\Resources\Audits\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AuditForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('auditable_type')
                    ->required(),
                TextInput::make('auditable_id')
                    ->required()
                    ->numeric(),
                Select::make('audit_type')
                    ->options([
                        'outgoing' => 'Outgoing',
                        'incoming' => 'Incoming',
                        'documentale' => 'Documentale',
                        'ispezione' => 'Ispezione',
                    ])
                    ->required(),
                Select::make('authority_type')
                    ->options([
                        'garante' => 'Garante',
                        'oam' => 'Oam',
                        'ivass' => 'Ivass',
                        'banca_italia' => 'Banca italia',
                        'client' => 'Client',
                        'internal' => 'Internal',
                        'other' => 'Other',
                    ]),
                TextInput::make('authority_name'),
                TextInput::make('title')
                    ->required(),
                Textarea::make('scope')
                    ->columnSpanFull(),
                DatePicker::make('audit_date')
                    ->required(),
                DatePicker::make('followup_date'),
                Select::make('status')
                    ->options([
                        'planned' => 'Planned',
                        'in_progress' => 'In progress',
                        'completed' => 'Completed',
                        'pending_followup' => 'Pending followup',
                    ])
                    ->default('planned')
                    ->required(),
                Textarea::make('summary')
                    ->columnSpanFull(),
                Textarea::make('auditor_notes')
                    ->columnSpanFull(),
            ]);
    }
}
