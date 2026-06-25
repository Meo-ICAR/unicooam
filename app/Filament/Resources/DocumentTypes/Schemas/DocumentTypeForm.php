<?php

namespace App\Filament\Resources\DocumentTypes\Schemas;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class DocumentTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // ==========================================
                // 1. INFORMAZIONI PRIMARIE (Impatto immediato)
                // ==========================================
                TextColumn::make('name')
                    ->label('Nome documento')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('nature')
                    ->label('Natura Flusso')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'incoming' => 'info',
                        'template_fillable' => 'warning',
                        'compliance' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'incoming' => 'Da Ricevere',
                        'template_fillable' => 'Modulo da Compilare / Firmare',
                        'compliance' => 'Compliance / Regolamento',
                        default => $state,
                    })
                    ->sortable(),
                TextColumn::make('duration')
                    ->label('Validità')
                    ->formatStateUsing(function ($record) {
                        if (!$record->duration) {
                            return 'Nessuna scadenza';
                        }

                        $unit = match ($record->duration_unit) {
                            'hours' => 'Ore',
                            'days' => 'Giorni',
                            'months' => 'Mesi',
                            'years' => 'Anni',
                            default => 'Giorni'
                        };

                        return "{$record->duration} {$unit}";
                    })
                    ->placeholder('Senza scadenza'),
                IconColumn::make('is_monitored')
                    ->label('Da rinnovare periodicamente')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Select::make('renewed_by_id')
                    ->label('Documento per rinnovo (se differente da attuale)')
                    ->relationship('renewedBy', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                // ==========================================
                // 2. INFORMAZIONI SPECIFICHE (A chi e dove)
                // ==========================================
                TextColumn::make('target')
                    ->label('Applicabile a')
                    ->badge()
                    ->color('gray')
                    ->state(function ($record): array {
                        $map = [
                            'company' => ['flag' => $record->is_company, 'label' => 'Azienda'],
                            'employee' => ['flag' => $record->is_employee, 'label' => 'Dipendente'],
                            'cliente' => ['flag' => $record->is_client, 'label' => 'Mandante'],
                            'fornitore' => ['flag' => $record->is_agent, 'label' => 'Produttore'],
                            'practice' => ['flag' => $record->is_practice, 'label' => 'Pratica'],
                            'person' => ['flag' => $record->is_person, 'label' => 'Persona'],
                        ];

                        $targets = [];
                        foreach ($map as $key => $config) {
                            if ($config['flag']) {
                                $targets[] = $config['label'];
                            }
                        }
                        return $targets;
                    })
                    ->placeholder('Nessun target'),
                TextColumn::make('phase')
                    ->label('Fase Processo')
                    ->sortable()
                    ->toggleable(),
                // ==========================================
                // 3. FLAG TECNICI E STATI (In fondo alla riga)
                // ==========================================
                IconColumn::make('is_signed')
                    ->label('Check sia firmato')
                    ->boolean()
                    ->trueIcon('heroicon-o-pencil-square')
                    ->falseIcon('')  // Lascia vuoto se falso per non appesantire la riga
                    ->toggleable(),
                TextColumn::make('code')
                    ->label('Codice')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('slug')
                    ->label('Slug URL')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('nature')
                    ->label('Natura Flusso')
                    ->options([
                        'incoming' => 'Da Ricevere',
                        'template_fillable' => 'Moduli da Compilare',
                        'compliance' => 'Compliance / Regolamenti',
                    ]),
                TernaryFilter::make('is_company')
                    ->label('Target Azienda')
                    ->placeholder('Tutti'),
                TernaryFilter::make('is_employee')
                    ->label('Target Dipendente')
                    ->placeholder('Tutti'),
                TernaryFilter::make('is_signed')
                    ->label('Richiede Firma')
                    ->placeholder('Tutti'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
