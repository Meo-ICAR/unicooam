<?php

namespace App\Filament\Resources\Clients\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nominativo')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                IconColumn::make('is_person')
                    ->label('Persona fisica')
                    ->boolean(),
                TextColumn::make('tax_code')
                    ->label('Codice fiscale')
                    ->searchable(),
                TextColumn::make('vat_number')
                    ->label('Partita IVA')
                    ->searchable(),
                TextColumn::make('employer.name')
                    ->label('Datore di lavoro')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('employee_roles')
                    ->label('Ruoli')
                    ->badge()
                    ->separator(',')
                    ->searchable(),
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
