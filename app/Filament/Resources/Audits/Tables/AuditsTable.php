<?php

namespace App\Filament\Resources\Audits\Tables;

use App\Enums\AuditStatus;
use App\Filament\Exports\DynamicGroupExport;
use App\Filament\Utils\TableHelper;
use App\Models\Company;
use App\Models\Task;
use App\ValueObjects\OamSemester;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;
use pxlrbt\FilamentExcel\Actions\ExportAction;

class AuditsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Ordinamento di default: i più recenti pianificati o eseguiti in alto
            ->defaultSort('scheduled_at', 'desc')
            //  ->contentFooter(fn($records) => new HtmlString($records?->count() > 0 ? 'Totale record: ' . $records->count() : 'Nessun record trovato'))
            ->columns([
                // 2. Soggetto Controllato (Risolve il polimorfismo mostrando il nome reale dell'agente/impiegato)
                TableHelper::polymorphicColumn('auditable', 'Soggetto Controllato'),
                // 3. Organismo di Vigilanza (Relazione con la tabella organizations)
                TextColumn::make('organization.acronym')
                    ->label('Ente Vigilante')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Audit Interno'),
                // 4. Chi esegue il controllo (Auditor)
                TextColumn::make('auditor_name')
                    ->label('Auditor'),
                // 5. Date critiche (Formattate in formato italiano)
                TextColumn::make('scheduled_at')
                    ->label('Pianificata')
                    ->date('d/m/y')
                    ->sortable(),
                TextColumn::make('executed_at')
                    ->label('Eseguita')
                    ->date('d/m/y')
                    ->sortable()
                    ->toggleable(),
                // 6. Stati ed Esiti (Visualizzazione a Badge avanzata)
                TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->sortable(),  // Se usi l'Enum con HasColor/HasLabel, Filament fa tutto da solo
                TextColumn::make('outcome')
                    ->label('Esito')
                    ->badge()
                    ->colors([
                        'success' => 'Passato',
                        'warning' => 'Con Rilievi',
                        'danger' => 'Fallito',
                    ])
                    ->sortable()
                    ->placeholder('In attesa di esito'),
                TextColumn::make('followup_date')
                    ->label('Follow-up')
                    ->date('d/m/y')
                    ->sortable(),
                //  ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('remediation_plan')
                    ->label('Remediation Plan')
                    ->sortable(),
                // 1. Identificazione e Protocollo
                TextColumn::make('protocol_number')
                    ->label('N. Protocollo')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Non protocollato')
                    ->weight('bold'),
                // 7. Campi secondari nascosti di default (Toggleable) per non intasare lo schermo
                TextColumn::make('origin_type')
                    ->label('Origine')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'internal' => 'Interno',
                        'external_incoming' => 'Ispezione Esterna',
                        default => $state,
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('execution_method')
                    ->label('Metodo')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('semestre_attuale')
                    ->label('Solo semestre in corso')
                    ->toggle() // <--- Trasforma la Checkbox in un interruttore Toggle grafico
                    ->default(true)
                    ->query(function (Builder $query, array $data): Builder {
                        return $data['isActive']
                            ? $query->perSemestreOam(OamSemester::getInBaseAlMeseCorrente())
                            : $query;
                    }),

                // Filtro rapido per stato dell'audit
                SelectFilter::make('status')
                    ->label('Stato Audit')
                    ->options(AuditStatus::class),
                // Filtro per Organismo di Vigilanza
                SelectFilter::make('organization_id')
                    ->label('Ente Richiedente')
                    ->relationship('organization', 'acronym')
                    ->preload(),
                // Filtro per record cancellati (Soft Deletes)
                TrashedFilter::make()
                    ->label('Cestino'),
                Filter::make('executed_at')
                    ->form([
                        DatePicker::make('execution_from')
                            ->label('Data esecuzione (Dal)'),
                        DatePicker::make('execution_to')
                            ->label('Data esecuzione (Al)'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['execution_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('executed_at', '>=', $date),
                            )
                            ->when(
                                $data['execution_to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('executed_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['execution_from'] ?? null) {
                            $indicators[] = 'Esecuzione dal: '.Carbon::parse($data['execution_from'])->format('d/m/y');
                        }

                        if ($data['execution_to'] ?? null) {
                            $indicators[] = 'Esecuzione al: '.Carbon::parse($data['execution_to'])->format('d/m/y');
                        }

                        return $indicators;
                    }),
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
            ])
            ->recordActions([
                EditAction::make(),
            ])
            // FIX CRITICO: Spostate le azioni di massa dentro bulkActions() invece di toolbarActions()
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('createDocumentationForAudit')
                        ->label('Aggiungi plico documentazione')
                        ->icon('heroicon-o-document-plus')
                        ->requiresConfirmation()
                        // 1. Definiamo il form all'interno del Modal della Bulk Action
                        ->form([
                            Select::make('task_id')
                                ->label('Seleziona il Task')
                                ->options(fn () => Task::where('taskable', 'audit')->pluck('name', 'id'))
                                ->searchable()
                                ->required(),
                        ])
                        // 2. Elaboriamo l'azione recuperando i dati compilati nel form ($data)
                        ->action(function (Collection $records, array $data) {
                            // Recuperiamo l'azienda principale
                            $company = Company::first();

                            if (! $company) {
                                Notification::make()
                                    ->title('Errore')
                                    ->body('Nessuna azienda trovata nel sistema.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            // Recuperiamo il SINGOLO task selezionato dall'utente nel form
                            $task = Task::with('documentTypes')->find($data['task_id']);

                            if (! $task) {
                                Notification::make()
                                    ->title('Errore')
                                    ->body('Task non trovato.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            $company_id = $company->id;
                            $createdCount = 0;

                            // 3. Cicliamo SOLO sui fornitori selezionati per questo specifico task
                            foreach ($records as $fornitore) {
                                $createdCount += $task->createDocumentation($company_id, $fornitore->id);
                            }

                            // 4. Notifica finale
                            if ($createdCount > 0) {
                                Notification::make()
                                    ->title('Documentazione generata')
                                    ->body("Creati con successo {$createdCount} nuovi documenti per il task \"{$task->name}\".")
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Tutto aggiornato')
                                    ->body('I documenti per questo task erano già tutti presenti per i fornitori selezionati.')
                                    ->info()
                                    ->send();
                            }
                        }),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
