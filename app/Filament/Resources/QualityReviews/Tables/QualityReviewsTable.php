<?php

namespace App\Filament\Resources\QualityReviews\Tables;

use App\Filament\Exports\DynamicGroupExport;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\ExportAction;

class QualityReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withCount([
                    'audits',
                    'audits as reviewed_count' => fn (Builder $q) => $q->whereNotNull('outcome'),
                    'audits as difformi_count' => fn (Builder $q) => $q->whereNotNull('outcome')->where('outcome', '!=', 'conforme'),
                ]))
            ->columns([
                TextColumn::make('name')->label('Nome')->searchable()->weight('bold'),
                TextColumn::make('reviewer.name')->label('Responsabile')->placeholder('Non assegnato'),
                TextColumn::make('period_from')->label('Dal')->date('d/m/y'),
                TextColumn::make('period_to')->label('Al')->date('d/m/y'),
                TextColumn::make('audits_count')->label('Pratiche')->badge(),
                TextColumn::make('reviewed_count')
                    ->label('Valutate')
                    ->badge()
                    ->color(fn ($record) => $record->reviewed_count >= $record->audits_count ? 'success' : 'warning'),
                TextColumn::make('difformi_count')->label('Difformi')->badge()->color('danger'),
                TextColumn::make('created_at')->label('Creato il')->dateTime('d/m/y')->sortable(),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exports([
                        DynamicGroupExport::make(),
                    ])
                    ->label('Esporta Excel')
                    ->color('success'),
            ])
            ->recordActions([
                EditAction::make()->label('Apri'),
            ]);
    }
}
