<?php

namespace App\Filament\Resources\ComplaintRegistries\Tables;

use App\Enums\ComplaintCategory;
use App\Enums\ComplaintMacroCategory;
use App\Enums\ComplaintStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ComplaintRegistriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
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
                    ->date('d/m/Y')
                    ->sortable(),
                // 3. ANAGRAFICA RECLAMANTE (Con fallback sul testo libero se non associato a un Model)
                TextColumn::make('complainant_name')
                    ->label('Reclamante')
                    ->searchable()
                    ->default(fn($record) => $record->complainant?->name ?? 'Dato non censito'),
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
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn($record) => $record->isOverdue() ? 'danger' : 'gray')
                    ->weight(fn($record) => $record->isOverdue() ? 'bold' : 'normal')
                    ->description(fn($record) => $record->isOverdue() ? '⚠️ SCADUTO' : null),
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
                // Filtro rapido per isolare le scadenze violate
                Filter::make('scaduti')
                    ->label('🚨 Mostra Scaduti Legali')
                    ->query(fn(Builder $query) => $query
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
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
