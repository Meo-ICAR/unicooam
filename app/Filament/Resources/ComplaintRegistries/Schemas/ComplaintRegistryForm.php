<?php

namespace App\Filament\Resources\ComplaintRegistries\Schemas;

use App\Enums\ComplaintStatus;
use App\Models\COMPILANCE\ComplaintRegistry;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ComplaintRegistryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('informazioni_reclamo')
                    ->label('Informazioni Reclamo')
                    ->description('Dettagli principali del reclamo')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->schema([
                        TextInput::make('complaint_number')
                            ->label('Numero Reclamo')
                            ->placeholder('es. REC-2025-001')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default(function (): string {
                                $year = now()->year;
                                $last = ComplaintRegistry::withTrashed()
                                    ->whereYear('created_at', $year)
                                    ->count();
                                return sprintf('REC-%d-%03d', $year, $last + 1);
                            }),
                        TextInput::make('complainant_name')
                            ->label('Nome Richiedente')
                            ->placeholder('Mario Rossi')
                            ->required()
                            ->maxLength(255),
                        Select::make('category')
                            ->label('Categoria')
                            ->options([
                                'delay' => 'Ritardo',
                                'behavior' => 'Comportamento',
                                'privacy' => 'Privacy',
                                'fraud' => 'Frode',
                                'quality' => 'Qualità',
                                'contract' => 'Contrattuale',
                                'other' => 'Altro',
                            ])
                            ->required()
                            ->default('other'),
                        TextInput::make('financial_impact')
                            ->label('Impatto Finanziario (€)')
                            ->numeric()
                            ->step(0.01)
                            ->prefix('€')
                            ->default(0.0)
                            ->placeholder('0.00'),
                        RichEditor::make('description')
                            ->label('Descrizione')
                            ->placeholder('Descrivi dettagliatamente il problema...')
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('gestione_stato')
                    ->label('Gestione Stato')
                    ->description('Aggiorna lo stato del reclamo')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        Select::make('status')
                            ->label('Stato Attuale')
                            ->options(ComplaintStatus::class)
                            ->required()
                            ->default(ComplaintStatus::OPEN)
                            ->live(),
                    ]),
            ]);
    }
}
