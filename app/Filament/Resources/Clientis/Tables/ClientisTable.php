<?php

namespace App\Filament\Resources\Clientis\Tables;

use App\Filament\Exports\DynamicGroupExport;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\ExportAction;

class ClientisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
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
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Ragione Sociale'),
                TextColumn::make('type')
                    ->badge()
                    ->colors([
                        'primary' => 'Banca',
                        'warning' => 'Broker',
                        'success' => 'Captive',
                    ])
                    ->label('Tipo'),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'ATTIVO',
                        'danger' => 'RECEDUTO',
                        'warning' => 'SOSPESO',
                        'secondary' => 'SCADUTO',
                    ])
                    ->label('Stato Mandato'),
                IconColumn::make('is_active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->label('Attivo'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'ATTIVO' => 'Attivo',
                        'SCADUTO' => 'Scaduto',
                        'RECEDUTO' => 'Receduto',
                        'SOSPESO' => 'Sospeso',
                    ])
                    ->label('Stato'),
                SelectFilter::make('principal_type')
                    ->options([
                        'banca' => 'Banca',
                        'broker' => 'Broker',
                        'captive' => 'Broker Captive',
                    ])
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
