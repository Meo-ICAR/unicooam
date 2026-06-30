<?php

namespace App\Filament\Resources\Clientis\Tables;

use App\Filament\Exports\DynamicGroupExport;
use App\Models\Clienti;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\ExportAction;

class ClientisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('nome')
            ->modifyQueryUsing(fn($query) => $query->where('is_dummy', false))
            ->headerActions([
                ExportAction::make()
                    ->exports([
                        DynamicGroupExport::make(),
                        //    ->groupBy('Produttore')  // Campo per il raggruppamento
                        //    ->sumColumns(['Provvigione']),  // Campi da sommare
                    ])
                    ->label('Esporta Excel')
                    ->color('success'),
            ])
            ->columns([
                TextColumn::make('nome')
                    ->searchable()
                    ->sortable()
                    ->label('Ragione Sociale'),
                TextColumn::make('principal_type')
                    ->sortable()
                    ->badge()
                    ->colors([
                        'primary' => 'Banca',
                        'warning' => 'Broker',
                        'success' => 'Captive',
                    ])
                    ->label('Tipo'),
                ToggleColumn::make('is_active')
                    //   ->boolean()
                    //   ->trueIcon('heroicon-o-check-circle')
                    //   ->falseIcon('heroicon-o-x-circle')
                    ->label('Attivo'),
                TextColumn::make('oam_codes_count')
                    ->counts('oamCodes')
                    ->label('Convenzioni')
                    ->sortable()
                    ->badge()  // Opzionale: racchiude il numero in un badge grafico molto pulito
                    ->color('primary'),
                TextColumn::make('piva')
                    ->searchable()
                    ->sortable()
                    ->label('Partita IVA'),
                TextColumn::make('stipulated_at')
                    ->date('d/m/Y')
                    ->sortable()
                    ->label('Stipula'),
                TextColumn::make('dismissed_at')
                    ->date('d/m/Y')
                    ->sortable()
                    ->label('Recesso'),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Stato')
                    ->placeholder('Tutti')
                    ->trueLabel('Solo Attivi')
                    ->falseLabel('Solo Inattivi')
                    ->default(true),
                SelectFilter::make('principal_type')
                    ->options([
                        '--' => '---',
                        'banca' => 'Banca',
                        'broker' => 'Broker',
                        'captive' => 'Broker Captive',
                        'assicurazione' => 'Assicurazione',
                    ])
                    ->default('banca')
                    ->label('Tipo Mandante'),
                SelectFilter::make('ivass_section')
                    ->options([
                        'A' => 'Sezione A',
                        'B' => 'Sezione B',
                        'C' => 'Sezione C',
                        'D' => 'Sezione D',
                        'E' => 'Sezione E',
                    ])
                    ->label('Sezione IVASS'),
            ])
            ->actions([
                //  ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                // DeleteBulkAction::make(),
            ]);
    }
}
