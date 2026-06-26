<?php

namespace App\Filament\Resources\SuspiciousActivityReports\Tables;

use App\Filament\Exports\DynamicGroupExport;
use App\Models\Company;
use App\Models\Employee;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\ExportAction;

class SuspiciousActivityReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
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
                // ==========================================
                // 1. INFORMAZIONI PRIMARIE
                // ==========================================
                TextColumn::make('reported_at')
                    ->label('Data Segnalazione')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'investigated' => 'info',
                        'reported' => 'danger',
                        'archived' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'pending' => 'In Attesa',
                        'investigated' => 'In Investigazione',
                        'reported' => 'Segnalata a UIF',
                        'archived' => 'Archiviata',
                        default => $state,
                    })
                    ->sortable(),
                // Risoluzione della polimorfica "reportable" (Chi ha fatto la segnalazione?)
                TextColumn::make('reportable')
                    ->label('Segnalatore')
                    ->state(function ($record) {
                        if (!$record->reportable)
                            return 'N/D';

                        // Restituisce il nome se è un Employee/Agent, adattalo ai tuoi campi reali
                        return $record->reportable->name ?? $record->reportable->full_name ?? 'Soggetto Polimorfico';
                    })
                    ->description(fn($record): string => match ($record->reportable_type) {
                        'employee' => '👤 Dipendente',
                        'fornitore', 'agent' => '💼 Agente / Produttore',
                        default => '🔗 Altro',
                    }),
                // ==========================================
                // 2. DETTAGLI E CONTENUTO
                // ==========================================
                TextColumn::make('client.name')  // Presuppone la relazione 'client' impostata nel Model
                    ->label('Mandante Correlato')
                    ->searchable()
                    ->placeholder('Nessun mandante'),
                TextColumn::make('anomalies_codes')
                    ->label('Codici Anomalia')
                    ->badge()
                    ->color('gray')
                    ->separator(',')  // Se salvato come array/stringa separata
                    ->placeholder('Nessun codice')
                    ->toggleable(),
                TextColumn::make('description')
                    ->label('Descrizione Segnalazione')
                    ->limit(40)  // Taglia il testo per non spaccare il layout della tabella
                    ->searchable()
                    ->tooltip(fn($record) => $record->description),  // Mostra il testo completo al passaggio del mouse
            ])
            ->filters([
                // Filtro per lo Stato della pratica
                SelectFilter::make('status')
                    ->label('Stato Segnalazione')
                    ->options([
                        'pending' => 'In Attesa',
                        'investigated' => 'In Investigazione',
                        'reported' => 'Segnalata',
                        'archived' => 'Archiviata',
                    ]),
                // Filtro per tipologia di segnalatore polimorfo
                SelectFilter::make('reportable_type')
                    ->label('Tipo Segnalatore')
                    ->options([
                        'employee' => 'Solo Dipendenti',
                        'fornitore' => 'Solo Agenti / Produttori',
                    ]),
                // Filtro Cestino per Soft Deletes
                TrashedFilter::make()
                    ->label('Cestino'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
