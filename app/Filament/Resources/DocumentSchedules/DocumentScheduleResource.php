<?php

namespace App\Filament\Resources\DocumentSchedules;

use App\Filament\Exports\DynamicGroupExport;
use App\Filament\Resources\DocumentSchedules\Pages\ManageDocumentSchedules;
use App\Filament\Traits\HasPlanAccess; // Assicurati di importare questo!
use App\Filament\Utils\TableHelper; // Importa la tua nuova Mailable
use App\Mail\ScadenziarioReportMail;
use App\Models\Document;
use App\Models\DocumentSchedule;
use App\Models\DocumentType;
use App\Models\EmailTemplate;
use App\Models\Employee;
use App\Models\PROFORMA\Fornitore;
use App\Services\DocumentReminderService;
use Unico\Core\Documents\Reminders\DocumentReminderService as CoreDocumentReminderService;
use Unico\Core\Documents\Reminders\ReminderDraft;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;  // Importante per il form nel modal
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
// use Illuminate\Support\Collection;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use pxlrbt\FilamentExcel\Actions\ExportAction;
use UnitEnum;

class DocumentScheduleResource extends Resource
{
    use HasPlanAccess;

    protected static ?string $model = DocumentSchedule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Scadenziario';

    protected static ?string $modelLabel = 'Scadenza';

    protected static ?string $pluralModelLabel = 'Scadenze';

    //    protected static UnitEnum|string|null $navigationGroup = 'Anagrafiche';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 30;

    public static function table(Table $table): Table
    {
        return $table
            // ->recordTitleAttribute('name')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('document.media'))
            ->defaultPaginationPageOption(50)
            ->reorderableColumns()
            ->defaultSort('expires_at')
            ->groups([
                Group::make('documentable_group_key')
                    ->label('Destinatario')
                    ->titlePrefixedWithLabel(false)
                    ->getTitleFromRecordUsing(fn (DocumentSchedule $record): string => $record->entity_name)
                    ->collapsible(),
            ])
         //   ->defaultGroup('documentable_group_key')
            ->columns([
                TextColumn::make('expires_at')
                    ->label('Scadenza')
                    ->date('d/m/y')
                    ->sortable()
                    ->color(fn (DocumentSchedule $record): string => $record->expires_at?->isPast() ? 'danger' : 'gray'),

                TextColumn::make('entity_name')
                    ->label('Soggetto / Entità')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('document_name')
                    ->label('Documento')
                    ->searchable()
                    ->sortable()
                    ->url(fn (DocumentSchedule $record): ?string => static::downloadUrl($record), shouldOpenInNewTab: true)
                    ->color(fn (DocumentSchedule $record): ?string => static::downloadUrl($record) ? 'info' : null),

                /*
                 * TextColumn::make('document_type_name')
                 *     ->label('Tipo')
                 *     ->badge()
                 *     ->sortable(),
                 */
                TextColumn::make('days_until_expiry')
                    ->label('Giorni')
                    ->badge()
                    ->sortable()
                    ->color(fn (DocumentSchedule $record): string => match (true) {
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
                    ->searchable(query: fn (Builder $query, string $search): Builder => static::searchByRecipientEmail($query, $search)),
            ])
            ->filters([
                // FILTRO 2: Selezione per Tipo Documento (Relazione)

                SelectFilter::make('document_type_name')
                    ->label('Tipo Documento')
    // Carica dinamicamente solo i nomi unici realmente presenti nella tabella
                    ->options(function () {
                        return DocumentSchedule::query()
                            ->whereNotNull('document_type_name')
                            ->where('document_type_name', '!=', '-') // Esclude eventuali placeholder vuoti
                            ->distinct()
                            ->pluck('document_type_name', 'document_type_name')
                            ->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'] ?? null,
                            fn (Builder $query, $value) => $query->where('document_type_name', $value)
                        );
                    }),
                Filter::make('scaduti')
                    ->label('Già scaduti')
                    ->query(fn (Builder $query): Builder => $query->whereDate('expires_at', '<', now()->toDateString())),
                Filter::make('in_scadenza_7')
                    ->label('In scadenza imminente (7 gg)')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereDate('expires_at', '>=', now()->toDateString())
                        ->whereDate('expires_at', '<=', now()->addDays(7)->toDateString())),
                Filter::make('in_scadenza')
                    ->label('In scadenza (30 gg)')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereDate('expires_at', '>=', now()->toDateString())
                        ->whereDate('expires_at', '<=', now()->addDays(30)->toDateString())),
                TableHelper::polymorphicFilter('documentable_type', 'Destinatari'),
                Filter::make('last_sent_at')
                    ->label('Ultimo sollecito prima 7gg')
                    ->query(fn (Builder $query): Builder => $query->whereDate('expires_at', '<', now()->subDays(7)->toDateString())),

                /*
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
                    */
            ])
            ->recordActions([
                Action::make('renew')
                    ->label('Aggiorna')
                    ->icon('heroicon-o-pencil-square')
                    ->action(function (DocumentSchedule $record, array $data): void {
                        $document = $record->document;
                        $newAttachmentPath = $data['new_attachment'] ?? null;

                        if (filled($newAttachmentPath)) {
                            // Un nuovo allegato è stato caricato: creiamo una nuova versione
                            // del documento con il file aggiornato, mentre quello attuale
                            // viene conservato come versione precedente (stato "scaduto").
                            $newDocument = $document->renew($data['emitted_at']);
                            $newDocument->addMediaFromDisk($newAttachmentPath, 'public')
                                ->toMediaCollection('documents');
                            Storage::disk('public')->delete($newAttachmentPath);

                            $record->update([
                                'document_id' => $newDocument->id,
                                'expires_at' => $newDocument->expires_at,
                                'status' => $newDocument->status,
                            ]);
                        } else {
                            // Nessun nuovo allegato: aggiorniamo solo la data di emissione
                            // sul documento esistente.
                            $document->emitted_at = $data['emitted_at'];
                            $document->save();
                            $document->refresh();

                            $record->update([
                                'expires_at' => $document->expires_at,
                            ]);
                        }

                        Notification::make()
                            ->title('Documento aggiornato')
                            ->success()
                            ->send();
                    })
                    ->form([
                        DatePicker::make('emitted_at')
                            ->label('Nuova data di emissione')
                            ->default(now())
                            ->maxDate(now())
                            ->required(),
                        FileUpload::make('new_attachment')
                            ->label('Nuovo allegato (opzionale)')
                            ->helperText('Se carichi un file, viene creata una nuova versione del documento con il nuovo allegato; il documento attuale viene conservato come versione precedente con stato "scaduto".')
                            ->disk('public')
                            ->directory('document-renewals')
                            ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                            ->maxSize(20480),
                    ]),
                //  DeleteAction::make(),
            ])->recordAction('renew')
            ->headerActions([
                ExportAction::make()
                    ->exports([
                        DynamicGroupExport::make()
                            ->modifyQueryUsing(fn (Builder $query) => static::excludeInactiveEntities($query)),
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
                        static::syncScheduleTable();

                        Notification::make()
                            ->title('Scadenziario aggiornato')
                            ->body('Tutte le scadenze sono state ricalcolate con successo.')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('deleteDocument')
                        ->label('Elimina documenti')
                        ->requiresConfirmation() // Opzionale: aggiunge una conferma per sicurezza
                        ->action(function (Collection $records): void {
                            Log::debug('=== INIZIO BULK ACTION ===');

                            $recordsCount = $records->count();
                            $deletedCount = 0;

                            foreach ($records as $record) {
                                // Verifica se la relazione esiste prima di eliminare per evitare errori
                                if ($record->document) {
                                    $record->document->delete();
                                    $record->delete(); // Elimina anche il record di DocumentSchedule associato
                                    $deletedCount++;
                                }
                            }

                            Log::debug("=== FINE BULK ACTION: Eliminati $deletedCount di $recordsCount ===");

                            // Opzionale: notifica a schermo l'avvenuta eliminazione
                            Notification::make()
                                ->title('Documenti eliminati con successo')
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('inviaSollecito')
                        ->label('Invia sollecito')
                        ->icon('heroicon-o-envelope')
                        ->requiresConfirmation()
                        ->color('warning')
                   //     ->modalWidth(Width::FourExtraLarge)// <--- AGGIUNGI QUESTA RIGA
                        ->form([
                            Select::make('email_template_id')
                                ->label('Template Email')
                                ->options(EmailTemplate::where('is_active', true)
                                    ->where(fn ($q) => $q->whereNull('type')->orWhere('type', 'sollecito'))
                                    ->where(fn ($q) => $q->whereNull('app_identifier')->orWhere('app_identifier', 'unicooam'))
                                    ->pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function (Set $set, ?string $state) {
                                    if ($state) {
                                        $template = EmailTemplate::find($state);
                                        $set('subject', $template?->subject);
                                        $set('body', $template?->body);
                                    } else {
                                        $set('subject', null);
                                        $set('body', null);
                                    }
                                }),
                            // LEGENDA DELLE VARIABILI DISPONIBILI
                            Placeholder::make('legend')
                                ->label('Tag disponibili per il template')
                                ->visible(fn (Get $get) => filled($get('email_template_id')))
                                ->content(new HtmlString('
                <div class="flex flex-wrap gap-2 text-sm text-gray-600 dark:text-gray-400">
                    <span class="px-2 py-1 bg-gray-100 dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700 font-mono text-xs text-primary-600"><code>{agente_nome}</code></span>
                    <span class="px-2 py-1 bg-gray-100 dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700 font-mono text-xs text-primary-600"><code>{n_documenti}</code></span>
                    <span class="px-2 py-1 bg-gray-100 dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700 font-mono text-xs text-primary-600"><code>{elenco_documenti}</code></span>
                </div>
                <p class="text-xs text-gray-500 mt-1">Puoi copiare e incollare questi tag nell\'oggetto o nel testo dell\'email; verranno sostituiti automaticamente all\'invio.</p>
            ')),
                            TextInput::make('subject')
                                ->label('Oggetto')
                                ->required()
                                ->visible(fn (Get $get) => filled($get('email_template_id'))),
                            RichEditor::make('body')
                                ->label("Testo dell'email")
                                ->required()
                                ->visible(fn (Get $get) => filled($get('email_template_id'))),
                            Toggle::make('is_demo')
                                ->helperText("Se attivato, invia l'email di sollecito a te stesso invece di inviarla ai destinatari")
                                ->default(true)
                                ->label('Invio in modalità demo (nessuna email vera verrà inviata)'),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            Log::debug('=== INIZIO BULK ACTION ===');

                            $emailUser = auth()->user()->email;
                            $sentCount = 0;
                            $erroreIsCompany = false;

                            // Filtra e raggruppa
                            $groupedRecords = $records->filter(function ($record) use (&$erroreIsCompany) {
                                if ($record->documentable_type === 'company') {
                                    $erroreIsCompany = true;

                                    return false;
                                }

                                return filled($record->entity?->email);
                            })->groupBy(fn ($record) => $record->entity->email);

                            foreach ($groupedRecords as $email => $userRecords) {
                                self::processAndSendEmailForUserGroup(
                                    $email,
                                    $userRecords,
                                    $data,
                                    $emailUser
                                );
                                $sentCount++;
                            }

                            Notification::make()
                                ->title("Solleciti inviati a {$sentCount} destinatari")
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }

    /** Link di download del documento collegato, se ha un allegato. */
    protected static function downloadUrl(DocumentSchedule $record): ?string
    {
        return $record->document?->download_url;
    }

    /**
     * Cerca per email del destinatario. Dipendenti e fornitori stanno su database diversi
     * (fornitori su PROFORMA), quindi una whereHas sulla relazione polimorfica non è utilizzabile:
     * si cercano gli id nel database di ciascun tipo e si filtra per (tipo, id).
     *
     * @param  Builder<DocumentSchedule>  $query
     * @return Builder<DocumentSchedule>
     */
    protected static function searchByRecipientEmail(Builder $query, string $search): Builder
    {
        return $query->orWhere(function (Builder $query) use ($search): void {
            foreach ([Employee::class, Fornitore::class] as $modelClass) {
                $ids = $modelClass::query()
                    ->where('email', 'like', "%{$search}%")
                    ->pluck('id')
                    ->map(fn ($id): string => (string) $id);

                if ($ids->isNotEmpty()) {
                    $query->orWhere(fn (Builder $query) => $query
                        ->where('documentable_type', (new $modelClass)->getMorphClass())
                        ->whereIn('documentable_id', $ids));
                }
            }

            $query->orWhereRaw('1 = 0');
        });
    }

    /**
     * Invia il sollecito per i documenti di un destinatario con il servizio del pacchetto: grado, template e casella mittente
     * (company e tipo «sollecito») li decide `compose()`; oggetto e testo scelti o cambiati nel modulo hanno la precedenza.
     * Demo: va all'indirizzo di prova e non conta come sollecito; altrimenti l'indirizzo di prova è in copia.
     */
    protected static function processAndSendEmailForUserGroup(string $email, $userRecords, array $data, string $fallbackEmail): void
    {
        $agentName = (string) ($userRecords->first()->entity_name ?? 'Agente');
        $documents = $userRecords->map(fn ($record) => $record->document)->filter()->unique('id')->values();

        if ($documents->isEmpty()) {
            return; // Nessun documento valido trovato
        }

        $service = app(CoreDocumentReminderService::class);
        $draft = $service->compose($documents, 'unicooam');
        $subject = filled($data['subject'] ?? null) ? $service->renderText($data['subject'], $agentName, $documents) : $draft->subject;
        $body = filled($data['body'] ?? null) ? $service->renderText($data['body'], $agentName, $documents, appendList: true) : $draft->body;
        $isDemo = ! empty($data['is_demo']);

        // Il destinatario è quello mostrato nella tabella (email dell'entità).
        $draft = new ReminderDraft($documents, $agentName, $email, $draft->severity, $draft->template, $draft->sender, $subject, $body);

        $attachments = static::reminderAttachments($userRecords);
        $result = $service->send($draft, options: [
            'demo_to' => $isDemo ? $fallbackEmail : null,
            'cc' => $isDemo ? null : $fallbackEmail,
            'attachments' => $attachments,
        ]);

        Log::info('Sollecito: '.$result['sent'].' inviati, '.$result['failed'].' falliti, '.count($attachments).' allegati.'.($isDemo ? ' [MODALITÀ DEMO]' : ''));
    }

    /** Allegati del sollecito: i file del tipo documento (collection `documents`) o, in mancanza, il suo `document_url`. */
    protected static function reminderAttachments($userRecords): array
    {
        $attachments = [];

        foreach ($userRecords->pluck('document_type_name')->filter()->unique() as $name) {
            $docType = DocumentType::where('name', $name)->first();

            if ($docType === null) {
                continue;
            }

            if ($docType->hasMedia('documents')) {
                foreach ($docType->getMedia('documents') as $media) {
                    $attachments[] = $media;
                }
            } elseif (filled($docType->document_url)) {
                $attachments[] = $docType->document_url;
            }
        }

        return $attachments;
    }

    /**
     * Ricalcola la tabella document_schedules a partire dai documenti da monitorare.
     * Condivisa dall'azione "sincronizzaScadenziario" e dall'API di sincronizzazione.
     */
    public static function syncScheduleTable(): void
    {
        $reminderService = app(DocumentReminderService::class);

        $documents = static::excludeInactiveEntities($reminderService->scheduleQuery())->get();
        $rows = [];

        foreach ($documents as $doc) {
            $entityName = $doc->documentable?->name
                ?? $doc->documentable?->protocol_number
                ?? $doc->documentable?->summary
                ?? '-';

            $rows[] = [
                'document_id' => $doc->id,
                'documentable_group_key' => $doc->documentable_type.'|'.$doc->documentable_id,
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

        DocumentSchedule::truncate();
        foreach (array_chunk($rows, 500) as $chunk) {
            DocumentSchedule::insert($chunk);
        }
    }

    /**
     * Esclude dall'export le scadenze di dipendenti eliminati (deleted_at)
     * o cessati (termination_date valorizzata) e di fornitori eliminati
     * (deleted_at) o dismessi (dismissed_at valorizzata).
     *
     * Vale per query su DocumentSchedule e Document (stesse colonne documentable_*).
     *
     * @param  Builder<DocumentSchedule|Document>  $query
     * @return Builder<DocumentSchedule|Document>
     */
    public static function excludeInactiveEntities(Builder $query): Builder
    {
        $inactiveSupplierIds = Fornitore::withTrashed()
            ->where(fn (Builder $supplier) => $supplier->whereNotNull('deleted_at')->orWhereNotNull('dismissed_at'))
            ->pluck('id');

        return $query
            ->where(function (Builder $query): void {
                $query->where('documentable_type', '!=', (new Employee)->getMorphClass())
                    ->orWhereHasMorph(
                        'documentable',
                        [Employee::class],
                        fn (Builder $employee) => $employee->whereNull('termination_date'),
                    );
            })
            ->where(function (Builder $query) use ($inactiveSupplierIds): void {
                $query->where('documentable_type', '!=', (new Fornitore)->getMorphClass())
                    ->orWhereNotIn('documentable_id', $inactiveSupplierIds);
            });
    }

    /**
     * Genera lo stesso file Excel prodotto dal bottone "Esporta Excel"
     * (ExportAction + DynamicGroupExport) al di fuori del contesto di una
     * richiesta Livewire, per poterlo allegare a un'email.
     */
    public static function generateScheduleExcelBinary(): string
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $livewire = app(ManageDocumentSchedules::class);
        $livewire->bootedInteractsWithTable();

        $export = DynamicGroupExport::make()->hydrate($livewire);

        return Excel::raw($export, ExcelFormat::XLSX);
    }

    /**
     * Aggiorna lo scadenziario e invia via email il relativo export Excel.
     */
    public static function syncAndEmailScheduleReport(): void
    {
        static::syncScheduleTable();

        $fileContents = static::generateScheduleExcelBinary();
        $fileName = 'scadenziario_'.now()->format('Y-m-d_H-i').'.xlsx';

        Mail::to('hassistosrl@gmail.com')
            ->cc('piergiuseppe.meo@gmail.com')
            ->send(new ScadenziarioReportMail(
                'Scadenziario documenti aggiornato',
                'In allegato il file Excel con lo scadenziario documenti aggiornato.',
                $fileContents,
                $fileName,
            ));
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
