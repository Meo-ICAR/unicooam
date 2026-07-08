<?php

namespace App\Filament\Resources\DocumentSchedules;

use App\Enums\DocumentStatus;
use App\Filament\Exports\DynamicGroupExport;
use App\Filament\Resources\DocumentSchedules\Pages\ManageDocumentSchedules;
use App\Filament\Utils\TableHelper; // Assicurati di importare questo!
use App\Mail\DocumentReminderMail; // Importa la tua nuova Mailable
use App\Models\DocumentSchedule;
use App\Models\DocumentType;
use App\Models\EmailTemplate;
use App\Services\DocumentReminderService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;  // Importante per il form nel modal
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
use Illuminate\Support\HtmlString;
use pxlrbt\FilamentExcel\Actions\ExportAction;
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
            ->reorderableColumns()
            ->defaultSort('expires_at')
            ->groups([
                Group::make('documentable_group_key')
                    ->label('Destinatario')
                    ->titlePrefixedWithLabel(false)
                    ->getTitleFromRecordUsing(fn (DocumentSchedule $record): string => $record->entity_name)
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
                    ->color(fn (DocumentSchedule $record): string => $record->expires_at?->isPast() ? 'danger' : 'gray'),
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
                    ->searchable(),
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
                            $data['value'],
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
                        ->form([
                            Select::make('email_template_id')
                                ->label('Template Email')
                                ->options(EmailTemplate::where('is_active', true)->pluck('name', 'id'))
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
                                // Richiama il nuovo metodo separato!
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

    /**
     * FUNZIONE ESTERNA: Gestisce l'elaborazione dei documenti e l'invio della mail
     */
    protected static function processAndSendEmailForUserGroup(string $email, $userRecords, array $data, string $fallbackEmail): void
    {
        $agentName = $userRecords->first()->entity_name ?? 'Agente';
        $documentNames = [];
        $attachments = [];

        // 1. Estrazione Documenti e Allegati
        foreach ($userRecords as $record) {
            // Chiamata corretta sull'istanza della relazione per evitare l'errore non-static
            // $documentName = $record->document?->renewedBy()?->name ?? $record->document_name;
            $documentName = $record->document_name; // Fallback se il nome del documento non è disponibile
            if ($documentName) {
                $documentNames[] = '- '.$documentName;

                // AGGIORNAMENTO CONTATORI SUL DOCUMENTO REALE (Solo se NON è un invio DEMO)
                if (empty($data['is_demo']) && $record->document) {
                    // Aggiorna tutto in un colpo solo direttamente sul database tramite query builder
                    $record->document->update([
                        'reminders_count' => $record->document->reminders_count + 1,
                        'last_sent_at' => now(),
                        'status' => DocumentStatus::PENDING->value, // .value assicura di salvare la stringa 'pending' nel DB
                    ]);
                }

                // Estrazione allegati dal DocumentType
                if ($documentType = $record->document_type_name) {
                    $docType = DocumentType::where('name', $documentType)->first();

                    if ($docType) {
                        // 1. Controlla se ci sono media nella collection corretta ('documents')
                        if ($docType->hasMedia('documents')) {
                            // Essendo una relazione multipla, prendiamo tutti i file caricati
                            foreach ($docType->getMedia('documents') as $media) {
                                $attachments[] = $media->getPath();
                            }
                        }
                        // 2. Fallback sul campo di testo 'document_url' se valorizzato
                        elseif (filled($docType->document_url)) {
                            $attachments = $docType->document_url;
                        }
                    }
                }
            }
        }

        if (empty($documentNames)) {
            return; // Salta se nessun documento valido trovato
        }

        // 2. Sostituzione Variabili Template
        $documentCount = count($documentNames);
        $documentListString = implode('<br>', $documentNames);

        $subject = str_replace(
            ['{agente_nome}', '{n_documenti}', '{elenco_documenti}'],
            [$agentName, $documentCount, implode(' | ', $documentNames)],
            $data['subject'] ?? 'Sollecito'
        );

        $body = str_replace(
            ['{agente_nome}', '{n_documenti}', '{elenco_documenti}'],
            [$agentName, $documentCount, $documentListString],
            $data['body'] ?? ''
        );

        // 3. Invio effettivo tramite la Mailable di Laravel
        $targetEmail = ! empty($data['is_demo']) ? $fallbackEmail : $email;

        Mail::to($targetEmail)->send(new DocumentReminderMail($subject, $body, $attachments));

        // Log::info("Email di sollecito inviata a {$targetEmail} con ".count($attachments).' allegati.'.(! empty($data['is_demo']) ? ' [MODALITÀ DEMO]' : ''));
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
