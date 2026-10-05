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
use Illuminate\Database\Eloquent\Builder;

class OamCodesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(function () {
                return OamCode::query()->where('is_dummy', false);
            })
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
                    ->label('Prodotto')
                    ->placeholder('Tutti')
                    ->trueLabel('Solo Attivi')
                    ->falseLabel('Solo Inattivi')
                    ->default(true),
                TernaryFilter::make('clienti_count')
                    ->label('Convenzioni')
                    ->placeholder('Tutti')
                    ->trueLabel('Con convenzioni')
                    ->falseLabel('Senza convenzioni')
                    ->queries(
                        true: fn (Builder $query) => $query->hasConvenzioni(),
                        false: fn (Builder $query) => $query->doesntHaveConvenzioni(),
                    ),
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
