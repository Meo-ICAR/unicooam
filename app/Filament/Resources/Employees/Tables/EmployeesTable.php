<?php

namespace App\Filament\Resources\Employees\Tables;

// use App\Filament\Traits\CanExportTable;
use App\Filament\Exports\DynamicGroupExport;
// use App\Models\Rui;
use App\Models\BPM\Employee;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
// use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use pxlrbt\FilamentExcel\Actions\ExportAction;

// use Maatwebsite\Excel\Facades\Excel;

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
                TextColumn::make('employee_types')
                    ->label('Ruolo')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('hiring_date')
                    ->label('Data assunzione')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('termination_date')
                    ->label('Data cessazione')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('numero_iscrizione_rui')
                    ->label('Cod. OAM')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Indirizzo email')
                    ->searchable(),
                TextColumn::make('coordinator.name')
                    ->label('Coordinato da')
                    ->searchable()
                    ->placeholder('Nessun coordinatore'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //  DeleteBulkAction::make(),
                ]),

                /*
                 * Action::make('match_employee_rui')
                 *     ->label('Abbina Dipendenti a OAM')
                 *     ->icon('heroicon-o-link')
                 *     ->color('warning')
                 *     ->action(function () {
                 *         try {
                 *             $companyId = Auth::user()->company_id;
                 *             $matchedCount = 0;
                 *             $errors = [];
                 *
                 *             // Get all employee
                 *             $employees = Employee::where('company_id', $companyId)
                 *                 ->where('numero_iscrizione_rui', null)
                 *                 ->get();
                 *
                 *             foreach ($employees as $employee) {
                 *                 // Try to find matching RUI record by name
                 *                 $rui = Rui::where('cognome_nome', 'like', '%' . $employee->name . '%')
                 *                     ->first();
                 *
                 *                 if ($rui && !$employee->numero_iscrizione_rui) {
                 *                     // Update agent with RUI registration number
                 *                     $employee->update([
                 *                         'numero_iscrizione_rui' => $rui->numero_iscrizione_rui,
                 *                         'oam_at' => $rui->data_iscrizione,
                 *                         'oam_name' => $rui->cognome_nome
                 *                     ]);
                 *                     $matchedCount++;
                 *                 }
                 *             }
                 *
                 *             Notification::make()
                 *                 ->title('Abbinamento Agenti a OAM completata')
                 *                 ->body("Abbinate trovate: {$matchedCount}, Errori: " . count($errors))
                 *                 ->success()
                 *                 ->send();
                 *         } catch (\Exception $e) {
                 *             Notification::make()
                 *                 ->title('Errore abbina Agenti a OAM')
                 *                 ->body('Errore durante abbina: ' . $e->getMessage())
                 *                 ->danger()
                 *                 ->send();
                 *         }
                 *     }),
                 * Action::make('import_employees_excel')
                 *     ->label('Importa Dipendenti Excel')
                 *     ->icon('heroicon-o-document-arrow-up')
                 *     ->color('success')
                 *     ->form([
                 *         FileUpload::make('import_file_excel')
                 *             ->label('File Excel')
                 *             ->helperText('Carica un file Excel con i dati dei dipendenti')
                 *             ->acceptedFileTypes(['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                 *             ->maxSize(10240)  // 10MB
                 *             ->directory('employee-imports')
                 *             ->visibility('public')
                 *             ->required(),
                 *     ])
                 *     ->action(function (array $data) {
                 *         try {
                 *             $filePath = storage_path('app/public/' . $data['import_file_excel']);
                 *             $companyId = Auth::user()->company_id;
                 *             $filename = basename($data['import_file_excel']);
                 *
                 *             $importService = new \App\Services\EmployeeImportService($companyId);
                 *             $results = $importService->import($filePath);
                 *
                 *             Notification::make()
                 *                 ->title('Importazione Excel completata')
                 *                 ->body("Importazione da {$filename} completata. Importate: {$results['imported']}, Aggiornate: {$results['updated']}, Errori: {$results['errors']}")
                 *                 ->success()
                 *                 ->send();
                 *         } catch (\Exception $e) {
                 *             Notification::make()
                 *                 ->title('Errore importazione Excel')
                 *                 ->body('Errore durante importazione: ' . $e->getMessage())
                 *                 ->danger()
                 *                 ->send();
                 *         }
                 *     }),
                 */
            ]);
    }
}
