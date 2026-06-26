<?php

namespace App\Filament\Resources\OamCodes\Tables;

use App\Models\OamCode;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class OamCodesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            //     ->query(function () {
            //         return OamCode::query()->where('is_dummy', false)->withCount('clienti');  // Aggiunge il conteggio dei clienti associati a ogni OAM
            //     })
            ->columns([
                TextColumn::make('tipo_prodotto')
                    ->label('Tipo prodotto')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('description')
                    ->label('Descrizione')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('clienti_count')
                    ->counts('clienti')
                    ->label('N. Convenzioni')
                    ->sortable(),
                // STATO E INQUADRAMENTO
                ToggleColumn::make('is_active')
                    ->label('Attivo')
                    //  ->boolean()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Stato Agente')
                    ->placeholder('Tutti')
                    ->trueLabel('Solo Attivi')
                    ->falseLabel('Solo Inattivi')
                    ->default(true),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
