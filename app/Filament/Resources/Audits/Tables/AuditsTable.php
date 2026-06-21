<?php

namespace App\Filament\Admin\Resources\Audits\Tables;

use App\Models\Audit;
use App\Models\Client;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class AuditsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('audit_date')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('direction')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state === 'outgoing' ? '↗ Outgoing' : '↙ Incoming')
                    ->color(fn($state) => $state === 'outgoing' ? 'info' : 'warning'),

                TextColumn::make('title')
                    ->label('Titolo')
                    ->searchable()
                    ->limit(45)
                    ->tooltip(fn($record) => $record->title),

                TextColumn::make('auditable.name')
                    ->label('Soggetto')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('authority_type')
                    ->label('Autorità / Origine')
                    ->formatStateUsing(fn($state) => Audit::getAuthorityTypeOptions()[$state] ?? '—')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->formatStateUsing(fn($state) => Audit::getStatusOptions()[$state] ?? $state)
                    ->color(fn($state) => match ($state) {
                        'planned'          => 'gray',
                        'in_progress'      => 'warning',
                        'completed'        => 'success',
                        'pending_followup' => 'danger',
                        default            => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('open_findings_count')
                    ->label('Rilievi aperti')
                    ->counts('openFindings')
                    ->badge()
                    ->color(fn($state) => $state > 0 ? 'danger' : 'success'),

                TextColumn::make('followup_date')
                    ->label('Follow-up')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn($record) => $record->followup_date?->isPast() && $record->status === 'pending_followup'
                        ? 'danger' : null)
                    ->placeholder('—'),
            ])
            ->defaultSort('audit_date', 'desc')
            ->filters([
                SelectFilter::make('direction')
                    ->label('Tipo')
                    ->options(['outgoing' => 'Outgoing', 'incoming' => 'Incoming']),

                SelectFilter::make('status')
                    ->label('Stato')
                    ->options(Audit::getStatusOptions()),

                TrashedFilter::make(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
