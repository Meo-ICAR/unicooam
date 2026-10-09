<?php

namespace App\Filament\Resources\QualityReviews\RelationManagers;

use App\Enums\AuditStatus;
use App\Filament\Exports\DynamicGroupExport;
use App\Filament\Resources\Audits\AuditResource;
use App\Models\Audit;
use App\Models\PROFORMA\Pratica;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\ExportAction;

class AuditsRelationManager extends RelationManager
{
    protected static string $relationship = 'audits';

    protected static ?string $title = 'Pratiche da controllare';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->modifyQueryUsing(fn ($query) => $query->withCount('findings')->with('auditable'))
            ->columns([
                TextColumn::make('pratica')
                    ->label('Pratica')
                    ->state(fn (Audit $record) => $record->auditable?->name ?? 'N/D'),
                TextColumn::make('agente')
                    ->label('Agente')
                    ->state(fn (Audit $record) => $record->auditable instanceof Pratica ? $record->auditable->denominazione_agente : null),
                TextColumn::make('banca')
                    ->label('Banca')
                    ->state(fn (Audit $record) => $record->auditable instanceof Pratica ? $record->auditable->denominazione_banca : null),
                TextColumn::make('erogato')
                    ->label('Erogato')
                    ->state(fn (Audit $record) => $record->auditable instanceof Pratica ? $record->auditable->erogato : null)
                    ->money('EUR'),
                TextColumn::make('outcome')
                    ->label('Esito')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'conforme' => 'Conforme',
                        'con_rilievi' => 'Con rilievi',
                        'fallito' => 'Grave',
                        default => $state,
                    })
                    ->color(fn (?string $state) => match ($state) {
                        'conforme' => 'success',
                        'con_rilievi' => 'warning',
                        'fallito' => 'danger',
                        default => 'gray',
                    })
                    ->placeholder('Da valutare'),
                TextColumn::make('findings_count')->label('Rilievi')->badge(),
                TextColumn::make('auditor_notes')->label('Annotazioni')->limit(60)->toggleable(),
            ])
            ->filters([
                SelectFilter::make('da_valutare')
                    ->label('Stato valutazione')
                    ->options(['si' => 'Da valutare', 'no' => 'Valutate'])
                    ->query(fn ($query, array $data) => match ($data['value'] ?? null) {
                        'si' => $query->whereNull('outcome'),
                        'no' => $query->whereNotNull('outcome'),
                        default => $query,
                    }),
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
                Action::make('valuta')
                    ->label('Valuta')
                    ->icon(Heroicon::ClipboardDocumentCheck)
                    ->fillForm(fn (Audit $record): array => $record->only(['outcome', 'auditor_notes']))
                    ->schema([
                        Select::make('outcome')
                            ->label('Esito')
                            ->options([
                                'conforme' => 'Conforme, nessuna difformità',
                                'con_rilievi' => 'Difformità da correggere',
                                'fallito' => 'Difformità grave',
                            ])
                            ->required(),
                        Textarea::make('auditor_notes')
                            ->label('Annotazioni')
                            ->rows(5),
                    ])
                    ->action(function (Audit $record, array $data): void {
                        $record->update($data + [
                            'executed_at' => $record->executed_at ?? now(),
                            'status' => AuditStatus::COMPLETED,
                        ]);

                        Notification::make()->title('Valutazione salvata')->success()->send();
                    }),
                Action::make('rilievi')
                    ->label('Rilievi')
                    ->icon(Heroicon::ExclamationTriangle)
                    ->color('warning')
                    ->url(fn (Audit $record): string => AuditResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
