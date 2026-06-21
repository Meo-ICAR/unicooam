<?php

namespace App\Filament\Resources\Audits\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class AuditsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('auditable_type')
                    ->searchable(),
                TextColumn::make('auditable_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('audit_type')
                    ->badge(),
                TextColumn::make('authority_type')
                    ->badge(),
                TextColumn::make('authority_name')
                    ->searchable(),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('audit_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('followup_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
