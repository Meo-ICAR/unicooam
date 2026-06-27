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
                SelectFilter::make('employee_types')
                    ->label('Ruolo')
                    ->options([
                        'dipendente' => 'Dipendente',
                        'cda' => 'CdA',
                        'consulente' => 'Consulente',
                        'altro' => 'Altro',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Stato')
                    ->queries(
                        true: fn($query) => $query->whereNull('termination_date'),
                        false: fn($query) => $query->whereNotNull('termination_date'),
                    )
                    ->placeholder('Tutti')
                    ->trueLabel('Solo Attivi')
                    ->falseLabel('Solo Dimessi')
                    ->default(true),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('createtask')
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
            ->toolbarActions([
                //  BulkActionGroup::make([
                //  DeleteBulkAction::make(),
                //  ]),
            ]);
    }
}
