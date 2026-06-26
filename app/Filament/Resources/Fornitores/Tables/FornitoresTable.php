<?php

namespace App\Filament\Resources\Fornitores\Tables;

use App\Filament\Exports\DynamicGroupExport;
use App\Models\Company;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;  // Importante per il form nel modal
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use pxlrbt\FilamentExcel\Actions\ExportAction;

class FornitoresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
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
            ->selectable('is_active = 1')
            ->columns([
                // DATI PRINCIPALI
                TextColumn::make('name')
                    ->label('Ragione Sociale / Nome')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('piva')
                    ->label('P. IVA')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('stipulated_at')
                    ->label('Mandato')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('dismissed_at')
                    ->label('Cessato')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('oam')
                    ->label('OAM')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('isvass')
                    ->label('IVASS')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ivass_section')
                    ->label('Sez.')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                // STATO E INQUADRAMENTO
                ToggleColumn::make('is_active')
                    ->label('Attivo')
                    //  ->boolean()
                    ->sortable(),
                ToggleColumn::make('isdipendente')
                    ->label('Dipendente')
                    //     ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('enasarco')
                    ->label('Enasarco')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                // ALBI PROFESSIONALI (nascosti di default per non affollare la vista)
                TextColumn::make('ivass')
                    ->label('IVASS')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                // DATE
            ])
            ->filters([
                // Filtro per stato attivo/inattivo
                TernaryFilter::make('is_active')
                    ->label('Stato Agente')
                    ->placeholder('Tutti')
                    ->trueLabel('Solo Attivi')
                    ->falseLabel('Solo Inattivi')
                    ->default(true),
                // Filtro per tipologia di mandato Enasarco
                SelectFilter::make('enasarco')
                    ->label('Mandato Enasarco')
                    ->options([
                        'no' => 'Nessuno',
                        'monomandatario' => 'Monomandatario',
                        'plurimandatario' => 'Plurimandatario',
                        'societa' => 'Società',
                    ]),
                // Filtro per natura del collaboratore
                TernaryFilter::make('isdipendente')
                    ->label('Contratto')
                    ->placeholder('Tutti')
                    ->trueLabel('Solo Dipendenti')
                    ->falseLabel('Solo P. IVA / Agenzie'),
            ])
            ->actions([
                EditAction::make(),
                Action::make('createtaskForFornitore')
                    ->label('Crea plico')
                    ->icon('heroicon-o-document-plus')
                    ->form([
                        Select::make('task_id')
                            ->label('Seleziona il Task')
                            ->options(fn($record) => Task::getAvailableFor($record)->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                    ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('createDocumentationForFornitori')
                        ->label('Crea plico documentazione')
                        ->icon('heroicon-o-document-plus')
                        ->requiresConfirmation()
                        // 1. Definiamo il form all'interno del Modal della Bulk Action
                        ->form([
                            Select::make('task_id')
                                ->label('Seleziona il Task')
                                ->options(fn() => Task::where('taskable', 'fornitore')->pluck('name', 'id'))
                                ->searchable()
                                ->required(),
                        ])
                        // 2. Elaboriamo l'azione recuperando i dati compilati nel form ($data)
                        ->action(function (Collection $records, array $data) {
                            // Recuperiamo l'azienda principale
                            $company = Company::first();

                            if (!$company) {
                                Notification::make()
                                    ->title('Errore')
                                    ->body('Nessuna azienda trovata nel sistema.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            // Recuperiamo il SINGOLO task selezionato dall'utente nel form
                            $task = Task::with('documentTypes')->find($data['task_id']);

                            if (!$task) {
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
                ])
            ])
            ->emptyStateHeading('Nessun fornitore trovato')
            ->emptyStateDescription('Crea un nuovo fornitore o agente per iniziare.');
    }
}
