<?php

namespace App\Filament\Resources\CompanyInspections\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CompanyInspectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('n')
                    ->label('N°')
                    ->sortable()
                    ->width('60px'),
                TextColumn::make('name')
                    ->label('Ispezione')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('execution_method')
                    ->label('Metodo')
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'documentale' => 'Documentale',
                        'onsite' => 'In loco',
                        default => '—',
                    })
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'documentale' => 'info',
                        'onsite' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('dal')
                    ->label('Dal')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('al')
                    ->label('Al')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('ispectorName')
                    ->label('Ispettore')
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('documentale')
                    ->label('Solo documentali')
                    ->query(fn (Builder $query) => $query->where('execution_method', 'documentale')),
                Filter::make('onsite')
                    ->label('Solo in loco')
                    ->query(fn (Builder $query) => $query->where('execution_method', 'onsite')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('dal', 'desc');
    }
}
