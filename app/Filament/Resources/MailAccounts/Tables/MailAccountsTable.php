<?php

namespace App\Filament\Resources\MailAccounts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class MailAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email_address')
                    ->searchable(),
                IconColumn::make('is_pec')
                    ->boolean(),
                TextColumn::make('incoming_protocol')
                    ->badge(),
                TextColumn::make('incoming_host')
                    ->searchable(),
                TextColumn::make('incoming_port')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('incoming_username')
                    ->searchable(),
                TextColumn::make('incoming_encryption')
                    ->badge(),
                TextColumn::make('smtp_host')
                    ->searchable(),
                TextColumn::make('smtp_port')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('smtp_username')
                    ->searchable(),
                TextColumn::make('smtp_encryption')
                    ->badge(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('mailable_type')
                    ->searchable(),
                TextColumn::make('mailable_id')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
