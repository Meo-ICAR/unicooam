<?php

namespace App\Filament\Pages;

use App\Models\Document;
use App\Services\DocumentReminderService;
use App\Support\DocumentRecipientResolver;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use BackedEnum;
use UnitEnum;

class ScadenziarioDocumenti extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Scadenziario documenti';

    protected static ?string $title = 'Scadenziario documenti';

    // protected static UnitEnum|string|null $navigationGroup = 'Conformità';

    protected static ?int $navigationSort = 12;

    protected string $view = 'filament.pages.scadenziario-documenti';

    public function table(Table $table): Table
    {
        $recipientResolver = app(DocumentRecipientResolver::class);
        $reminderService = app(DocumentReminderService::class);

        return $table
            ->query(
                $reminderService
                    ->scheduleQuery()
                    ->selectRaw("documents.*, CONCAT(documentable_type, '|', documentable_id) as documentable_group_key")
            )
            ->defaultSort('expires_at')
            ->groups([
                Group::make('documentable_group_key')
                    ->label('Entità')
                    ->titlePrefixedWithLabel(false)
                    ->getTitleFromRecordUsing(fn(Document $record): string => $recipientResolver->groupLabel($record))
                    ->collapsible(),
            ])
            ->columns([
                TextColumn::make('name')
                    ->label('Documento')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('documentType.name')
                    ->label('Tipo')
                    ->badge()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label('Scadenza')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn(Document $record): string => $record->expires_at?->isPast() ? 'danger' : 'gray'),
                TextColumn::make('days_until_expiry')
                    ->label('Giorni')
                    ->state(fn(Document $record): string => (string) $reminderService->daysUntilExpiry($record))
                    ->badge()
                    ->color(fn(Document $record): string => match (true) {
                        $reminderService->daysUntilExpiry($record) < 0 => 'danger',
                        $reminderService->daysUntilExpiry($record) <= 7 => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('status')
                    ->label('Stato')
                    ->badge(),
                TextColumn::make('reminders_count')
                    ->label('Solleciti inviati')
                    ->counts('reminders')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('scaduti')
                    ->label('Solo scaduti')
                    ->query(fn(Builder $query): Builder => $query->whereDate('expires_at', '<', now()->toDateString())),
                Filter::make('in_scadenza')
                    ->label('In scadenza (30 gg)')
                    ->query(fn(Builder $query): Builder => $query
                        ->whereDate('expires_at', '>=', now()->toDateString())
                        ->whereDate('expires_at', '<=', now()->addDays(30)->toDateString())),
                SelectFilter::make('documentable_type')
                    ->label('Modello')
                    ->options([
                        'App\Models\Employee' => 'Dipendente',
                        'App\Models\Company' => 'Azienda',
                        'App\Models\Audit' => 'Audit',
                        'App\Models\ComplaintRegistry' => 'Reclamo',
                        'App\Models\PROFORMA\Clienti' => 'Istituto',
                        'App\Models\PROFORMA\Fornitore' => 'Produttore',
                    ]),
            ])
            ->recordActions([
                Action::make('inviaSollecito')
                    ->label('Invia sollecito')
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->requiresConfirmation()
                    ->action(function (Document $record) use ($reminderService): void {
                        $groupKey = $record->documentable_type . '|' . $record->documentable_id;
                        $stats = $reminderService->sendReminders(onlyDueToday: false, groupKey: $groupKey);

                        Notification::make()
                            ->title('Sollecito inviato')
                            ->body("Email inviate: {$stats['sent']}, fallite: {$stats['failed']}")
                            ->success()
                            ->send();
                    }),
            ])
            ->paginated([10, 25, 50]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('inviaSollecitiProgrammati')
                ->label('Invia solleciti programmati')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->requiresConfirmation()
                ->modalDescription('Invia email solo per documenti che raggiungono oggi una soglia di preavviso configurata.')
                ->action(function (): void {
                    $stats = app(DocumentReminderService::class)->sendReminders(onlyDueToday: true);

                    Notification::make()
                        ->title('Solleciti programmati inviati')
                        ->body("Gruppi: {$stats['groups']}, email documenti: {$stats['sent']}, saltati: {$stats['skipped']}")
                        ->success()
                        ->send();
                }),
            Action::make('inviaSollecitiForzati')
                ->label('Invia solleciti a tutti')
                ->icon(Heroicon::OutlinedBellAlert)
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('Invia sollecito per ogni gruppo entità/record presente nello scadenziario, indipendentemente dalla soglia odierna.')
                ->action(function (): void {
                    $stats = app(DocumentReminderService::class)->sendReminders(onlyDueToday: false);

                    Notification::make()
                        ->title('Solleciti inviati')
                        ->body("Gruppi: {$stats['groups']}, email documenti: {$stats['sent']}, fallite: {$stats['failed']}")
                        ->success()
                        ->send();
                }),
        ];
    }
}
