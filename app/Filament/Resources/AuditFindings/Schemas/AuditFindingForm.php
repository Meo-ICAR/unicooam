<?php

namespace App\Filament\Resources\AuditFindings\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AuditFindingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('audit_id')
                    ->relationship('audit', 'title')
                    ->required(),
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->required(),
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                Select::make('severity')
                    ->options([
            'observation' => 'Observation',
            'minor' => 'Minor',
            'major' => 'Major',
            'critical' => 'Critical',
        ])
                    ->default('minor')
                    ->required(),
                Toggle::make('requires_investigation')
                    ->required(),
                Textarea::make('investigation_notes')
                    ->columnSpanFull(),
                DatePicker::make('investigation_deadline'),
                Toggle::make('requires_corrective_action')
                    ->required(),
                Textarea::make('corrective_action_description')
                    ->columnSpanFull(),
                Select::make('remediation_id')
                    ->relationship('remediation', 'name'),
                DatePicker::make('corrective_action_deadline'),
                Select::make('status')
                    ->options([
            'open' => 'Open',
            'in_progress' => 'In progress',
            'resolved' => 'Resolved',
            'accepted_risk' => 'Accepted risk',
            'closed' => 'Closed',
        ])
                    ->default('open')
                    ->required(),
                DatePicker::make('resolved_at'),
                Textarea::make('resolution_notes')
                    ->columnSpanFull(),
            ]);
    }
}
