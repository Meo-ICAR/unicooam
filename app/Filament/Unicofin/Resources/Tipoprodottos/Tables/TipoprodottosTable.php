<?php

namespace App\Filament\Unicofin\Resources\Tipoprodottos\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TipoprodottosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('tipo_provvigioni')
                    ->badge(),
                TextColumn::make('code')
                    ->searchable(),
                IconColumn::make('is_external')
                    ->boolean(),
                IconColumn::make('is_oneclient')
                    ->boolean(),
                TextColumn::make('oam')
                    ->searchable(),

            ])
            ->filters([
                //
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
