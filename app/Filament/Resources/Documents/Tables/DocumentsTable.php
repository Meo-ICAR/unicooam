<?php

namespace App\Filament\Resources\Documents\Tables;

use App\Filament\Exports\DynamicGroupExport;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\ExportAction;

class DocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->headerActions([
                ExportAction::make()
                    ->exports([
                        DynamicGroupExport::make(),
                    ])
                    ->label('Esporta Excel')
                    ->color('success'),
            ])
            ->columns([
                // 1. IDENTIFICATIVI PRINCIPALI

                // 2. ENTITÀ COLLEGATA (Rapporto Polimorfico reso leggibile)
                TextColumn::make('documentable')
                    ->label('Collegato a')
                    ->state(function ($record) {
                        if (! $record->documentable) {
                            return '-';
                        }
                        // Estrae solo il nome della classe (es. "User" invece di "App\Models\User")
                        $type = class_basename($record->documentable_type);
                        // Cerca un attributo leggibile (name, title) o ripiega sull'ID
                        $name = $record->documentable->name
                            ?? $record->documentable->title
                            ?? "ID #{$record->documentable_id}";

                        return "[{$type}] {$name}";
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        // Permette la ricerca testuale anche sui campi dell'entità polimorfica
                        return $query->whereHasMorph('documentable', '*', function (Builder $q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('id', 'like', "%{$search}%");
                        });
                    }),

                // 3. TIPI DI DOCUMENTO
                TextColumn::make('name')
                    ->label('Nome Documento')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),
                // 4. DATE E SCADENZE
                // Sostituisci il vecchio TextColumn con questo:
                TextInputColumn::make('emitted_at')
                    ->label('Data Emissione')
                    ->type('date') // Attiva il selettore di date nativo
                    ->rules(['required', 'date']) // Forza la validazione del dato inserito
                    ->sortable(),

                TextColumn::make('expires_at')
                    ->label('Scadenza')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($record) => $record->expires_at && $record->expires_at->isPast() ? 'danger' : 'gray'),

                TextColumn::make('documentType.name')
                    ->label('Tipo')
                    ->searchable()
                    ->sortable(),

                // 5. STATO E UTILITY
                TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approvato', 'attivo' => 'success',
                        'bozza' => 'gray',
                        'scaduto' => 'danger',
                        default => 'warning',
                    })
                    ->searchable(),

            ])
            ->filters([
                // FILTRO 1: Selezione per Tipo Entità (Polimorfica)
                SelectFilter::make('documentable_type')
                    ->label('Tipo Entità Collegata')
                    ->options([
                        'App\Models\User' => 'Utenti',
                        'App\Models\Company' => 'Aziende',
                        'App\Models\Project' => 'Progetti',
                        // Aggiungi qui le altre classi del tuo progetto
                    ]),

                // FILTRO 2: Selezione per Tipo Documento (Relazione)
                SelectFilter::make('document_type_id')
                    ->label('Tipo Documento')
                    ->relationship('documentType', 'name')
                    ->preload(),

                // FILTRO 3: Selezione per Categoria (Doctype)
                SelectFilter::make('doctype')
                    ->label('Categoria')
                    ->options([
                        'modulo' => 'Modulo',
                        'procedura' => 'Procedura',
                        'template' => 'Template',
                    ]),

                // FILTRO 4: Selezione Intervallo di Scadenza
                Filter::make('expires_at')
                    ->label('Data Scadenza')
                    ->form([
                        DatePicker::make('expires_from')->label('Scadenza da'),
                        DatePicker::make('expires_until')->label('Scadenza a'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['expires_from'], fn ($q, $date) => $q->whereDate('expires_at', '>=', $date))
                            ->when($data['expires_until'], fn ($q, $date) => $q->whereDate('expires_at', '<=', $date));
                    }),

                TrashedFilter::make()
                    ->label('Eliminati'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
