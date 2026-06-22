<?php

namespace App\Filament\Resources\AuditFindings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class AuditFindingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('audit.title')
                    ->label('Audit')
                    ->searchable(),
                TextColumn::make('title')
                    ->label('Rilievo')
                    ->searchable(),
                TextColumn::make('severity')
                    ->label('Gravità')
                    ->badge(),
                IconColumn::make('requires_investigation')
                    ->label('Richiede istruttoria')
                    ->boolean(),
                TextColumn::make('investigation_deadline')
                    ->label('Scadenza istruttoria')
                    ->date('d/m/Y')
                    ->sortable(),
                IconColumn::make('requires_corrective_action')
                    ->label('Richiede azione correttiva')
                    ->boolean(),
                TextColumn::make('remediation_id')
                    ->label('ID rimedio')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('corrective_action_deadline')
                    ->label('Scadenza risoluzione')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Stato')
                    ->badge(),
                TextColumn::make('resolved_at')
                    ->label('Risolto il')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Creato il')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Aggiornato il')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label('Eliminato il')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Eliminati'),
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
