<?php

namespace App\Filament\Resources\Audits\Tables;

use App\Enums\AuditStatus;
use App\Filament\Exports\DynamicGroupExport;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextareaColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\ExportAction;

class AuditsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Ordinamento di default: i più recenti pianificati o eseguiti in alto
            ->defaultSort('scheduled_at', 'desc')
            ->columns([
                // 2. Soggetto Controllato (Risolve il polimorfismo mostrando il nome reale dell'agente/impiegato)
                TextColumn::make('auditable')
                    ->label('Soggetto Controllato')
                    ->state(fn($record) => $record->auditable?->full_name ?? $record->auditable?->name ?? 'N/D')
                    ->description(fn($record) => match ($record->auditable_type) {
                        'App\Models\Agent' => 'Collaboratore / Agente',
                        'App\Models\Employee' => 'Impiegato Interno',
                        default => str_replace('App\\Models\\', '', $record->auditable_type),
                    })
                    ->searchable(query: function ($query, string $search) {
                        // Permette di cercare nella tabella per nome/cognome del polimorfico
                        $query->whereHasMorph('auditable', ['App\Models\Agent', 'App\Models\Employee'], function ($q) use ($search) {
                            $q
                                ->where('full_name', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%");
                        });
                    }),
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
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('executed_at')
                    ->label('Eseguita')
                    ->date('d/m/Y')
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
                    ->date('d/m/Y')
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
                    ->formatStateUsing(fn(string $state): string => match ($state) {
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
                                fn(Builder $query, $date): Builder => $query->whereDate('executed_at', '>=', $date),
                            )
                            ->when(
                                $data['execution_to'],
                                fn(Builder $query, $date): Builder => $query->whereDate('executed_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['execution_from'] ?? null) {
                            $indicators[] = 'Esecuzione dal: ' . \Carbon\Carbon::parse($data['execution_from'])->format('d/m/Y');
                        }

                        if ($data['execution_to'] ?? null) {
                            $indicators[] = 'Esecuzione al: ' . \Carbon\Carbon::parse($data['execution_to'])->format('d/m/Y');
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
            // FIX CRITICO: Spostate le azioni di massa dentro bulkActions() invece di toolbarActions()
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
