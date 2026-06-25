<?php

namespace App\Filament\Resources\Tasks\Tables;

use App\Models\PROFORMA\Fornitore;
use App\Models\Company;
use App\Models\Document;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable(),
                TextColumn::make('description')
                    ->label('Descrizione')
                    ->searchable(),
                TextColumn::make('taskable')
                    ->label('Entità collegata')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->headerActions([
                Action::make('create')
                    ->label('Crea documentazione')
                    ->requiresConfirmation()
                    ->action(function () {
                        $company = Company::first();

                        if (!$company) {
                            Notification::make()->title('Errore')->body('Nessuna azienda trovata.')->danger()->send();
                            return;
                        }

                        $company_id = $company->id;
                        $fornitori = Fornitore::all();

                        // Manteniamo l'Eager Loading per le performance
                        $tasks = Task::with('documentTypes')->get();
                        $createdCount = 0;

                        foreach ($tasks as $task) {
                            // Caso AZIENDA: il record id coincide con il $company_id
                            if ($task->taskable === 'company') {
                                $createdCount += $task->createDocumentation($company_id, $company_id);
                            }

                            // Caso FORNITORE: cicliamo sui fornitori e passiamo l'id del singolo fornitore
                            if ($task->taskable === 'fornitore') {
                                foreach ($fornitori as $fornitore) {
                                    $createdCount += $task->createDocumentation($company_id, $fornitore->id);
                                }
                            }
                        }

                        // Notifica finale
                        if ($createdCount > 0) {
                            Notification::make()
                                ->title('Documentazione generata')
                                ->body("Sono stati creati con successo {$createdCount} nuovi documenti mancanti.")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Tutto aggiornato')
                                ->body('La documentazione era già completa.')
                                ->info()
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
