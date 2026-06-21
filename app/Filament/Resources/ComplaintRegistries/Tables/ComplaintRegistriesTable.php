<?php

namespace App\Filament\Resources\ComplaintRegistries\Tables;

use App\Enums\ComplaintStatus;
use App\Models\COMPILANCE\ComplaintRegistry;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

class ComplaintRegistriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('complaint_number')
                    ->label('Numero Reclamo')
                    ->searchable(),
                // ->copyable()
                // ->copyMessage('Numero reclamo copiato!')
                // ->copyableWithShortcuts(),
                TextColumn::make('complainant_name')
                    ->label('Nome Richiedente')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('category')
                    ->label('Categoria')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'delay' => 'warning',
                        'behavior' => 'info',
                        'privacy' => 'danger',
                        'fraud' => 'danger',
                        'quality' => 'primary',
                        'contract' => 'secondary',
                        'other' => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'delay' => 'Ritardo',
                        'behavior' => 'Comportamento',
                        'privacy' => 'Privacy',
                        'fraud' => 'Frode',
                        'quality' => 'Qualità',
                        'contract' => 'Contrattuale',
                        'other' => 'Altro',
                        default => $state,
                    }),
                TextColumn::make('description')
                    ->label('Descrizione')
                    ->searchable()
                    ->limit(100)
                    ->wrap(),
                TextColumn::make('financial_impact')
                    ->label('Impatto Finanziario')
                    ->money('EUR')
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'open' => 'danger',
                        'investigating' => 'warning',
                        'resolved' => 'success',
                        'rejected' => 'danger',
                        'closed' => 'secondary',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'open' => 'Aperto',
                        'investigating' => 'In Investigazione',
                        'resolved' => 'Risolto',
                        'rejected' => 'Rifiutato',
                        'closed' => 'Chiuso',
                        default => $state,
                    }),
                TextColumn::make('created_at')
                    ->label('Data Creazione')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('category')
                    ->label('Categoria')
                    ->options([
                        'delay' => 'Ritardo',
                        'behavior' => 'Comportamento',
                        'privacy' => 'Privacy',
                        'fraud' => 'Frode',
                        'quality' => 'Qualità',
                        'contract' => 'Contrattuale',
                        'other' => 'Altro',
                    ]),
                SelectFilter::make('status')
                    ->label('Stato')
                    ->options([
                        'open' => 'Aperto',
                        'investigating' => 'In Investigazione',
                        'resolved' => 'Risolto',
                        'rejected' => 'Rifiutato',
                        'closed' => 'Chiuso',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Nessun reclamo trovato')
            ->emptyStateDescription('Crea il tuo primo reclamo.')
            ->emptyStateIcon('heroicon-o-chat-bubble-left-right');
    }
}
