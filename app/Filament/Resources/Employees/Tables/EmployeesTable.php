<?php

namespace App\Filament\Resources\Employees\Tables;

// use App\Filament\Traits\CanExportTable;
use App\Filament\Exports\DynamicGroupExport;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Task;
use App\Models\User;
use App\ValueObjects\OamSemester;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
// use App\Models\Rui;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
// use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
// use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use pxlrbt\FilamentExcel\Actions\ExportAction;

class EmployeesTable
{
    // use CanExportTable;

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
            ->columns([
                TextColumn::make('name')
                    ->label('Nominativo')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('employee_roles')
                    ->label('Ruolo')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('hiring_date')
                    ->label('Data assunzione')
                    ->date('d/m/y')
                    ->sortable(),
                TextColumn::make('oam_at')
                    ->label('Data OAM')
                    ->date('d/m/y')
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Indirizzo email')
                    ->searchable(),
                TextColumn::make('termination_date')
                    ->label('Data cessazione')
                    ->date('d/m/y')
                    ->sortable(),
                TextColumn::make('crea_utente')
                    ->label('Crea utente')
                    ->state(fn (Employee $record): ?string => self::canCreateLinkedUser($record) ? 'Crea utente' : null)
                    ->placeholder('')
                    ->icon(fn (Employee $record): ?string => self::canCreateLinkedUser($record) ? 'heroicon-m-user-plus' : null)
                    ->color(fn (Employee $record): ?string => self::canCreateLinkedUser($record) ? 'primary' : null)
                    ->disabledClick(fn (Employee $record): bool => ! self::canCreateLinkedUser($record))
                    ->action(
                        Action::make('createUser')
                            ->label('Crea utente')
                            ->visible(fn (Employee $record): bool => self::canCreateLinkedUser($record))
                            ->requiresConfirmation()
                            ->modalHeading('Crea utente')
                            ->modalDescription(fn (Employee $record): string => "Verrà creato un utente con l'email {$record->email} e collegato a {$record->name}.")
                            ->action(function (Employee $record): void {
                                self::createLinkedUser($record);
                            }),
                    ),
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

                SelectFilter::make('employee_types')
                    ->label('Filtra per Ruolo')
                    ->options([
                        'dipendente' => 'Dipendente',
                        'cda' => 'CdA',
                        'compliance' => 'Compliance',
                        'audit' => 'Audit',
                        'quality' => 'Quality',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $data['value']
                            ? $query->whereJsonContains('employee_types', $data['value'])
                            : $query
                    ),

            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('createDocumentationForDipendente')
                        ->label('Aggiungi plico documentazione')
                        ->icon('heroicon-o-document-plus')
                        ->requiresConfirmation()
                        // 1. Definiamo il form all'interno del Modal della Bulk Action
                        ->form([
                            Select::make('task_id')
                                ->label('Seleziona il Task')
                                ->options(fn () => Task::where('taskable', 'employee')->pluck('name', 'id'))
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
                ]),
            ]);  // toolbarActions
    }

    private static function canCreateLinkedUser(Employee $record): bool
    {
        return $record->user_id === null && filled(trim((string) $record->email));
    }

    private static function createLinkedUser(Employee $record): void
    {
        if (! self::canCreateLinkedUser($record)) {
            return;
        }

        $email = Str::lower(trim((string) $record->email));
        $existing = User::withTrashed()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($existing !== null) {
            if ($existing->trashed()) {
                $existing->restore();
            }

            $linkedToAnotherEmployee = Employee::query()
                ->where('user_id', $existing->id)
                ->whereKeyNot($record->id)
                ->exists();

            if ($linkedToAnotherEmployee) {
                Notification::make()
                    ->title('Email già usata')
                    ->body('Esiste già un utente con questa email collegato a un altro collaboratore.')
                    ->danger()
                    ->send();

                return;
            }

            $record->update(['user_id' => $existing->id]);

            Notification::make()
                ->title('Utente collegato')
                ->body("{$record->name} è stato collegato all'utente esistente {$email}.")
                ->success()
                ->send();

            return;
        }

        $user = User::query()->create([
            'name' => $record->name ?: $email,
            'email' => $email,
            'role' => 'employee',
            'company_id' => $record->company_id,
            'profile_type' => 'employee',
            'profile_id' => $record->id,
        ]);

        $record->update(['user_id' => $user->id]);

        Notification::make()
            ->title('Utente creato')
            ->body("Creato l'utente {$email} e collegato a {$record->name}.")
            ->success()
            ->send();
    }
}
