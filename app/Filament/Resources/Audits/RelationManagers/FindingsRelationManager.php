<?php

namespace App\Filament\Resources\Audits\RelationManagers;

use App\Enums\FindingSeverity;
use App\Enums\FindingStatus;
use App\Filament\Resources\AuditFindings\Schemas\AuditFindingForm;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FindingsRelationManager extends RelationManager
{
    protected static string $relationship = 'findings';

    protected static ?string $title = 'Rilievi e Non Conformità';

    public function form(Schema $schema): Schema
    {
        return AuditFindingForm::configure($schema, true);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('severity')
                    ->label('Gravità')
                    ->badge()
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Rilievo riscontrato')
                    ->searchable()
                    ->description(fn ($record) => str($record->description)->limit(50)),
                TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->sortable(),
                TextColumn::make('corrective_action_deadline')
                    ->label('Scadenza Rimedio')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($record) => $record->isOverdue() ? 'danger' : 'gray')
                    ->weight(fn ($record) => $record->isOverdue() ? 'bold' : 'normal')
                    ->description(fn ($record) => $record->isOverdue() ? 'SCADUTO' : null),
                TextColumn::make('resolved_at')
                    ->label('Risolto il')
                    ->date('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('severity')
                    ->label('Gravità')
                    ->options(FindingSeverity::class),
                SelectFilter::make('status')
                    ->label('Stato')
                    ->options(FindingStatus::class),
                Filter::make('scaduti')
                    ->label('Rilievi Scaduti')
                    ->query(fn (Builder $query) => $query
                        ->whereIn('status', ['open', 'in_progress'])
                        ->where('corrective_action_deadline', '<', now())),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['company_id'] = $this->getOwnerRecord()->company_id;

                        return $data;
                    }),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }
}
