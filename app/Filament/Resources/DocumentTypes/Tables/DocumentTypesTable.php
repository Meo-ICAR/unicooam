<?php

namespace App\Filament\Resources\DocumentTypes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class DocumentTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('description')
                    ->searchable(),
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('codegroup')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('regex_pattern')
                    ->searchable(),
                TextColumn::make('priority')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('phase')
                    ->searchable(),
                IconColumn::make('is_person')
                    ->boolean(),
                IconColumn::make('is_company')
                    ->boolean(),
                IconColumn::make('is_employee')
                    ->boolean(),
                IconColumn::make('is_agent')
                    ->boolean(),
                IconColumn::make('is_principal')
                    ->boolean(),
                IconColumn::make('is_client')
                    ->boolean(),
                IconColumn::make('is_practice')
                    ->boolean(),
                IconColumn::make('is_signed')
                    ->boolean(),
                IconColumn::make('is_monitored')
                    ->boolean(),
                TextColumn::make('duration')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('emitted_by')
                    ->searchable(),
                IconColumn::make('is_sensible')
                    ->boolean(),
                IconColumn::make('is_template')
                    ->boolean(),
                IconColumn::make('is_stored')
                    ->boolean(),
                TextColumn::make('regex')
                    ->searchable(),
                IconColumn::make('is_endmonth')
                    ->boolean(),
                IconColumn::make('is_AiAbstract')
                    ->boolean(),
                IconColumn::make('is_AiCheck')
                    ->boolean(),
                TextColumn::make('min_confidence')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('allow_auto_verification')
                    ->boolean(),
                TextColumn::make('retention_years')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_by')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('updated_by')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('deleted_by')
                    ->numeric()
                    ->sortable(),
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
