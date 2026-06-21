<?php

namespace App\Filament\Resources\AuditFindings\Schemas;

use App\Enums\FindingSeverity;
use App\Enums\FindingStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AuditFindingForm
{
    public static function configure(Schema $schema, bool $isRelationManager = false): Schema
    {
        $components = [];

        // Se il form viene usato nel registro globale esterno, serve il select dell'audit
        if (!$isRelationManager) {
            $components[] = Select::make('audit_id')
                ->label('Audit di Riferimento')
                ->relationship('audit', 'title')
                ->searchable()
                ->preload()
                ->required()
                ->columnSpanFull();
        }

        // Componenti standard del rilievo
        $components = array_merge($components, [
            TextInput::make('title')
                ->label('Titolo del Rilievo')
                ->required()
                ->columnSpanFull(),
            Textarea::make('description')
                ->label('Descrizione della Non Conformità')
                ->required()
                ->rows(4)
                ->columnSpanFull(),
            Section::make('Inquadramento Rilievo')
                ->columns(2)
                ->components([
                    Select::make('severity')
                        ->label('Gravità')
                        ->options(FindingSeverity::class)
                        ->required(),
                    Select::make('status')
                        ->label('Stato del Rilievo')
                        ->options(FindingStatus::class)
                        ->required()
                        ->default(FindingStatus::Open)
                        ->live(),
                ]),
            Section::make('Istruttoria / Investigazione')
                ->columns(2)
                ->components([
                    Toggle::make('requires_investigation')
                        ->label('Richiede Approfondimento?')
                        ->live(),
                    DatePicker::make('investigation_deadline')
                        ->label('Scadenza Istruttoria')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->required(fn(Get $get) => $get('requires_investigation'))
                        ->visible(fn(Get $get) => $get('requires_investigation')),
                    Textarea::make('investigation_notes')
                        ->label('Note Istruttoria')
                        ->rows(3)
                        ->columnSpanFull()
                        ->visible(fn(Get $get) => $get('requires_investigation')),
                ]),
            Section::make('Rimedio / Azione Correttiva')
                ->columns(2)
                ->components([
                    Toggle::make('requires_corrective_action')
                        ->label('Richiede Azione Correttiva?')
                        ->default(true)
                        ->live(),
                    DatePicker::make('corrective_action_deadline')
                        ->label('Scadenza Risoluzione')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->required(fn(Get $get) => $get('requires_corrective_action'))
                        ->visible(fn(Get $get) => $get('requires_corrective_action')),
                    Textarea::make('corrective_action_description')
                        ->label('Descrizione del Rimedio Richiesto')
                        ->rows(3)
                        ->columnSpanFull()
                        ->visible(fn(Get $get) => $get('requires_corrective_action')),
                ]),
            Section::make('Esito e Chiusura')
                ->columns(2)
                ->visible(fn(Get $get) => in_array($get('status'), ['resolved', 'closed', 'accepted_risk']))
                ->components([
                    DatePicker::make('resolved_at')
                        ->label('Data Risoluzione/Chiusura')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->required(),
                    Textarea::make('resolution_notes')
                        ->label('Note di Chiusura (Azioni intraprese)')
                        ->rows(3)
                        ->required()
                        ->columnSpanFull(),
                ]),
        ]);

        return $schema->components($components);
    }
}
