<?php

namespace App\Filament\Resources\ComplaintRegistries\Tables;

use App\Enums\ComplaintCategory;
use App\Enums\ComplaintMacroCategory;
use App\Enums\ComplaintStatus;
use App\Filament\Exports\DynamicGroupExport;
use App\Models\Company;
use App\Models\Task;
use App\ValueObjects\OamSemester;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\ExportAction;

class ComplaintRegistriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('received_at', 'desc')
            //    ->contentFooter(fn($records) => $records?->count() > 0 ? 'Totale record: ' . $records->count() : 'Nessun record trovato')
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
            ->columns([
                // 1. CODICI E PROTOCOLLO
                TextColumn::make('protocol_number')
                    ->label('Num. Protocollo')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                // 2. TEMPISTICHE
                TextColumn::make('received_at')
                    ->label('Ricevuto il')
                    ->date('d/m/y')
                    ->sortable(),
                // 3. ANAGRAFICA RECLAMANTE (Con fallback sul testo libero se non associato a un Model)
                TextColumn::make('complainant_name')
                    ->label('Reclamante')
                    ->searchable()
                    ->default(fn ($record) => $record->complainant?->name ?? 'Dato non censito'),
                // 4. CLASSIFICAZIONE (Usa gli Enum automatici per i Badge)
                TextColumn::make('macro_category')
                    ->label('Macro Ambito')
                    ->badge()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('category')
                    ->label('Motivo / Categoria')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                // 5. RESPONSABILITÀ AZIENDALI
                TextColumn::make('agent.nome_area_o_agente')
                    ->label('Agente Coinvolto')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('bank.denominazione')
                    ->label('Banca Mandante')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                // 6. IMPATTO ECONOMICO CONTATO IN EURO
                TextColumn::make('financial_impact')
                    ->label('Impatto Ec.')
                    ->money('EUR')
                    ->sortable()
                    ->alignEnd(),
                // 7. STATO WORKFLOW
                TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->sortable(),
                // 8. MONITORAGGIO TERMINI DI LEGGE
                TextColumn::make('deadline_at')
                    ->label('Scadenza Risposta')
                    ->date('d/m/y')
                    ->sortable()
                    ->color(fn ($record) => $record->isOverdue() ? 'danger' : 'gray')
                    ->weight(fn ($record) => $record->isOverdue() ? 'bold' : 'normal')
                    ->description(fn ($record) => $record->isOverdue() ? '⚠️ SCADUTO' : null),
                IconColumn::make('is_extended')
                    ->label('Proroga')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('resolved_at')
                    ->label('Risolto il')
                    ->date('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                // TIMESTAMPS SISTEMA
                TextColumn::make('created_at')
                    ->label('Creato il')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
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

                // Filtro rapido per isolare le scadenze violate
                Filter::make('scaduti')
                    ->label('🚨 Mostra Scaduti Legali')
                    ->query(fn (Builder $query) => $query
                        ->whereNotIn('status', [ComplaintStatus::Accepted->value, ComplaintStatus::Rejected->value])
                        ->where('deadline_at', '<', now())),

                /*
                 * SelectFilter::make('status')
                 *     ->label('Stato Workflow')
                 *     ->options(ComplaintStatus::class),
                 * SelectFilter::make('macro_category')
                 *     ->label('Macro Categoria')
                 *     ->options(ComplaintMacroCategory::class),
                 * SelectFilter::make('category')
                 *     ->label('Dettaglio Categoria')
                 *     ->options(ComplaintCategory::class),
                 */
                TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('createDocumentationForComplaint')
                        ->label('Aggiungi plico documentazione')
                        ->icon('heroicon-o-document-plus')
                        ->requiresConfirmation()
                        // 1. Definiamo il form all'interno del Modal della Bulk Action
                        ->form([
                            Select::make('task_id')
                                ->label('Seleziona il Task')
                                ->options(fn () => Task::where('taskable', 'complaint')->pluck('name', 'id'))
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
