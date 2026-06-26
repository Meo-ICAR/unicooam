<?php

namespace App\Filament\Resources\DocumentSchedules;

use App\Filament\Exports\DynamicGroupExport;
use App\Filament\Resources\DocumentSchedules\Pages\ManageDocumentSchedules;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentSchedule;
use App\Models\Task;
use App\Services\DocumentReminderService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;  // Importante per il form nel modal
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
// use Illuminate\Support\Collection;
use pxlrbt\FilamentExcel\Actions\ExportAction;
use BackedEnum;
use UnitEnum;

class DocumentScheduleResource extends Resource
{
    protected static ?string $model = DocumentSchedule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Scadenziario documenti';

    protected static ?string $modelLabel = 'Scadenziario documenti';

    protected static ?string $pluralModelLabel = 'Scadenziario documenti';

    //    protected static UnitEnum|string|null $navigationGroup = 'Anagrafiche';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 30;

    public static function table(Table $table): Table
    {
        return $table
            // ->recordTitleAttribute('name')
            ->defaultSort('expires_at')
            ->groups([
                Group::make('documentable_group_key')
                    ->label('Destinatario')
                    ->titlePrefixedWithLabel(false)
                    ->getTitleFromRecordUsing(fn(DocumentSchedule $record): string => $record->entity_name)
                    ->collapsible(),
            ])
            ->defaultGroup('documentable_group_key')
            ->columns([
                TextColumn::make('entity_name')
                    ->label('Soggetto / Entità')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('document_name')
                    ->label('Documento')
                    ->searchable()
                    ->sortable(),

                /*
                 * TextColumn::make('document_type_name')
                 *     ->label('Tipo')
                 *     ->badge()
                 *     ->sortable(),
                 */
                TextColumn::make('expires_at')
                    ->label('Scadenza')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn(DocumentSchedule $record): string => $record->expires_at?->isPast() ? 'danger' : 'gray'),
                TextColumn::make('days_until_expiry')
                    ->label('Giorni')
                    ->badge()
                    ->sortable()
                    ->color(fn(DocumentSchedule $record): string => match (true) {
                        $record->days_until_expiry < 0 => 'danger',
                        $record->days_until_expiry <= 7 => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('status')
                    ->label('Stato')
                    ->badge(),
                TextColumn::make('reminders_count')
                    ->label('Solleciti inviati')
                    ->sortable(),
                TextColumn::make('last_sent_at')
                    ->label('Ultimo sollecito')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('Mai'),
                TextColumn::make('documentable.email')
                    ->label('Email')
                    ->searchable(),
            ])
            ->filters([
                Filter::make('scaduti')
                    ->label('Già scaduti')
                    ->query(fn(Builder $query): Builder => $query->whereDate('expires_at', '<', now()->toDateString())),
                Filter::make('in_scadenza_7')
                    ->label('In scadenza imminente (7 gg)')
                    ->query(fn(Builder $query): Builder => $query
                        ->whereDate('expires_at', '>=', now()->toDateString())
                        ->whereDate('expires_at', '<=', now()->addDays(7)->toDateString())),
                Filter::make('in_scadenza')
                    ->label('In scadenza (30 gg)')
                    ->query(fn(Builder $query): Builder => $query
                        ->whereDate('expires_at', '>=', now()->toDateString())
                        ->whereDate('expires_at', '<=', now()->addDays(30)->toDateString())),
                SelectFilter::make('documentable_type')
                    ->label('Destinatari')
                    ->options([
                        'fornitore' => 'Produttore',
                        'company' => 'Azienda',
                        'employee' => 'Dipendente',
                        'audit' => 'Audit',
                        'complaint' => 'Reclamo',
                        'cliente' => 'Istituto',
                    ]),
            ])
            ->recordActions([
                //  EditAction::make(),
                //  DeleteAction::make(),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exports([
                        DynamicGroupExport::make(),
                        //    ->groupBy('Produttore')  // Campo per il raggruppamento
                        //    ->sumColumns(['Provvigione']),  // Campi da sommare
                    ])
                    ->label('Esporta Excel')
                    ->color('success'),
                // IL BOTTONE DI AGGIORNAMENTO DATI ORA È UN'AZIONE DI HEADER DELLA RESOURCE
                Action::make('sincronizzaScadenziario')
                    ->label('Aggiorna scadenziario')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('info')
                    ->action(function (): void {
                        $reminderService = app(DocumentReminderService::class);

                        $documents = $reminderService->scheduleQuery()->get();
                        $rows = [];

                        foreach ($documents as $doc) {
                            $entityName = $doc->documentable?->name
                                ?? $doc->documentable?->protocol_number
                                ?? $doc->documentable?->summary
                                ?? '-';

                            $rows[] = [
                                'document_id' => $doc->id,
                                'documentable_group_key' => $doc->documentable_type . '|' . $doc->documentable_id,
                                'document_name' => $doc->name,
                                'document_type_name' => $doc->documentType?->name ?? '-',
                                'entity_name' => $entityName,
                                'documentable_type' => $doc->documentable_type,
                                'documentable_id' => $doc->documentable_id,
                                'expires_at' => $doc->expires_at?->toDateString(),
                                'days_until_expiry' => $reminderService->daysUntilExpiry($doc),
                                'status' => $doc->status,
                                'reminders_count' => $doc->reminders_count ?? $doc->reminders()->count(),
                                'last_sent_at' => $doc->last_sent_at,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }

                        //   \DB::transaction(function () use ($rows) {
                        DocumentSchedule::truncate();
                        foreach (array_chunk($rows, 500) as $chunk) {
                            DocumentSchedule::insert($chunk);
                        }
                        // });

                        Notification::make()
                            ->title('Scadenziario aggiornato')
                            ->body('Tutte le scadenze sono state ricalcolate con successo.')
                            ->success()
                            ->send();
                    }),

                /*
                 * Action::make('inviaSollecitiProgrammati')
                 *     ->label('Invia solleciti programmati')
                 *     ->icon(Heroicon::OutlinedPaperAirplane)
                 *     ->requiresConfirmation()
                 *     ->action(function (): void {
                 *         $stats = app(DocumentReminderService::class)->sendReminders(onlyDueToday: true);
                 *         Notification::make()
                 *             ->title('Solleciti programmati inviati')
                 *             ->body("Email inviate: {$stats['sent']}")
                 *             ->success()
                 *             ->send();
                 *     }),
                 */
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('inviaSollecito')
                        ->label('Invia sollecito -SIMULAZIONE --')
                        ->icon('heroicon-o-envelope')
                        ->requiresConfirmation()
                        ->color('warning')
                        ->action(function (Collection $records): void {
                            $sentCount = 0;
                            $nrecipients = 0;
                            $erroreIsCompany = false;

                            $recipient = null;
                            foreach ($records as $record) {
                                $document = Document::find($record->document_id);
                                if (!$document)
                                    continue;
                                if ($record->documentable_type <> 'fornitore') {
                                    $erroreIsCompany = true;
                                    continue;
                                }

                                // Tua logica di invio reale qui...
                                $record->increment('reminders_count', 1);
                                $record->update(['last_sent_at' => now()]);
                                $sentCount++;
                                $email = $record->entity?->email;
                                if (!$email)
                                    continue;

                                if ($recipient <> $email) {
                                    $nrecipients++;
                                    $recipient = $email;
                                }
                            }

                            Notification::make()
                                ->title("Inviati a {$nrecipients} destinatari {$sentCount} solleciti")
                                ->success()
                                ->send();
                            if ($erroreIsCompany) {
                                Notification::make()
                                    ->title('Alcuni documenti non sono stati inviati perché non sono associati ad un produttore')
                                    ->alert()
                                    ->send();
                            }
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDocumentSchedules::route('/'),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('document_id')
                    ->relationship('document', 'name')
                    ->required(),
                TextInput::make('documentable_group_key')
                    ->required(),
                TextInput::make('document_name')
                    ->required(),
                TextInput::make('document_type_name')
                    ->required(),
                TextInput::make('entity_name')
                    ->required(),
                TextInput::make('documentable_type')
                    ->required(),
                DatePicker::make('expires_at'),
                TextInput::make('days_until_expiry')
                    ->required()
                    ->numeric(),
                TextInput::make('status')
                    ->required(),
                TextInput::make('reminders_count')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
