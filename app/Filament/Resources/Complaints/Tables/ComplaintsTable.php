<?php

namespace App\Filament\Resources\Complaints\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ComplaintsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('subject')
                    ->label('Oggetto')
                    ->searchable()
                    ->limit(60),
                TextColumn::make('received_at')
                    ->label('Data Ricezione')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ricevuto'       => 'warning',
                        'in_lavorazione' => 'info',
                        'accolto'        => 'success',
                        'respinto'       => 'danger',
                        default          => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'ricevuto'       => 'Ricevuto',
                        'in_lavorazione' => 'In Lavorazione',
                        'accolto'        => 'Accolto',
                        'respinto'       => 'Respinto',
                        default          => $state,
                    }),
                TextColumn::make('resolved_at')
                    ->label('Data Risoluzione')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Creato il')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('status')
                    ->label('Stato')
                    ->options([
                        'ricevuto'       => 'Ricevuto',
                        'in_lavorazione' => 'In Lavorazione',
                        'accolto'        => 'Accolto',
                        'respinto'       => 'Respinto',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Nessun reclamo trovato')
            ->emptyStateDescription('Registra il primo reclamo ricevuto.')
            ->emptyStateIcon('heroicon-o-chat-bubble-oval-left-ellipsis');
    }
}
