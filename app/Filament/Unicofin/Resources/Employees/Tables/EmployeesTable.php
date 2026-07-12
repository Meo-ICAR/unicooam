<?php

namespace App\Filament\Unicofin\Resources\Employees\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class EmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->searchable(),
                TextColumn::make('branch.name')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('role_title')
                    ->searchable(),
                TextColumn::make('cf')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('pec')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('department')
                    ->searchable(),
                TextColumn::make('oam')
                    ->searchable(),
                TextColumn::make('oam_at')
                    ->date()
                    ->sortable(),
                TextColumn::make('oam_name')
                    ->searchable(),
                TextColumn::make('numero_iscrizione_rui')
                    ->searchable(),
                TextColumn::make('oam_dismissed_at')
                    ->date()
                    ->sortable(),
                TextColumn::make('ivass')
                    ->searchable(),
                TextColumn::make('hiring_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('termination_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('coordinated_by_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('employee_types')
                    ->searchable(),
                TextColumn::make('supervisor_type')
                    ->searchable(),
                TextColumn::make('privacy_role')
                    ->searchable(),
                TextColumn::make('retention_period')
                    ->searchable(),
                TextColumn::make('extra_eu_transfer')
                    ->searchable(),
                TextColumn::make('privacy_data')
                    ->searchable(),
                IconColumn::make('is_structure')
                    ->boolean(),
                IconColumn::make('is_ghost')
                    ->boolean(),
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
