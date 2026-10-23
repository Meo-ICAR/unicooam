<?php

namespace App\Filament\Resources\EmployeeTypes\Tables;

use App\Models\EmployeeType;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class EmployeeTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Ruolo')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Ufficio')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('code')
                    ->label('Codice')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('macro_area')
                    ->label('Macro area')
                    ->badge()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('managed_by_code')
                    ->label('Supervisionato da')
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('outsourcable_status')
                    ->label('Esternalizzabile')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => EmployeeType::OUTSOURCABLE[$state] ?? (string) $state)
                    ->sortable(),
                TextColumn::make('manager.name')
                    ->label('Responsabile')
                    ->placeholder('—'),
                TextColumn::make('companytype')
                    ->label('Tipo azienda')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_external')
                    ->label('Ruolo esterno')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('macro_area')
                    ->label('Macro area')
                    ->options(fn (): array => EmployeeType::query()->whereNotNull('macro_area')->distinct()->orderBy('macro_area')->pluck('macro_area', 'macro_area')->all()),
                SelectFilter::make('outsourcable_status')
                    ->label('Esternalizzabile')
                    ->options(EmployeeType::OUTSOURCABLE),
                TernaryFilter::make('code')
                    ->label('Ufficio dell\'organigramma')
                    ->nullable()
                    ->placeholder('Tutti'),
            ])
            ->defaultSort('name')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}
