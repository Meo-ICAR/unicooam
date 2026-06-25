<?php

namespace App\Filament\Resources\Audits\Tables;

use App\Enums\AuditStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class AuditsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // 1. CODICE PROTOCOLLO (Cliccabile per copiare al volo)
                TextColumn::make('auditable')
                    ->label('Soggetto Interessato')
                    ->state(function ($record) {
                        if (!$record->auditable)
                            return '-';

                        // Riconosce dinamicamente il campo stringa del modello collegato
                        return $record->auditable->ragione_sociale
                            ?? $record->auditable->nome_area_o_agente
                            ?? $record->auditable->denominazione
                            ?? $record->auditable->nome_organismo
                            ?? $record->auditable->name;
                    })
                    // Aggiunge una label secondaria grigia sotto il nome per capire COS'È quel soggetto
                    ->description(fn($record): string => match ($record->auditable_type) {
                        'App\Models\Company' => 'Sede Centrale / Azienda',
                        'App\Models\Fornitore' => 'Produttori',
                        'App\Models\Clienti' => 'Banca Mandante',
                        // 'App\Models\OrganismoVigilanza' => 'Autorità di Vigilanza',
                        default => 'Altro Soggetto',
                    }),
                // 7. DATE E CONTROLLO RITARDI
                TextColumn::make('scheduled_at')
                    ->label('Pianificato')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('executed_at')
                    ->label('Eseguito')
                    ->date('d/m/Y')
                    ->sortable()
                    // Se l'audit è scaduto e non è stato eseguito, colora il testo di rosso
                    ->color(fn($record) => $record->isDelayed() ? 'danger' : 'success')
                    ->placeholder(fn($record) => $record->isDelayed() ? 'IN RITARDO' : 'Da eseguire'),
                TextColumn::make('protocol_number')
                    ->label('Protocollo')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->placeholder('Non protocollato')
                    ->toggledHiddenByDefault(),  // Nascondibile se la tabella è troppo densa
                // 2. TITOLO AUDIT
                TextColumn::make('title')
                    ->label('Titolo / Oggetto')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                // 3. GESTIONE DINAMICA DEL SOGGETTO POLIMORFICO (MOLTO IMPORTANTE)
                // 4. ORIGINE E METODO DI ESECUZIONE
                TextColumn::make('origin_type')
                    ->label('Origine')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'internal' => 'Interno',
                        'incoming' => 'In Entrata',
                        'outgoing' => 'In Uscita',
                        default => $state,
                    })
                    ->badge()
                    ->color('gray'),
                TextColumn::make('execution_method')
                    ->label('Modalità')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'documentale' => 'Documentale',
                        'ispezione' => 'Ispezione in Loco',
                        default => $state,
                    })
                    ->icon(fn(string $state): string => match ($state) {
                        'ispezione' => 'heroicon-o-eye',
                        default => 'heroicon-o-document-text',
                    })
                    ->toggleable(),
                // 5. STATO (Prende colore e testo direttamente dal PHP Enum)
                TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->sortable(),
                // 6. ESITO FINALIZZATO
                TextColumn::make('outcome')
                    ->label('Esito')
                    ->badge()
                    ->color(fn(?string $state): string => match ($state) {
                        'superato' => 'success',
                        'con_rilievi' => 'warning',
                        'fallito' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(?string $state): string => match ($state) {
                        'superato' => 'Superato',
                        'con_rilievi' => 'Con Rilievi',
                        'fallito' => 'Non Superato',
                        default => 'N/D',
                    })
                    ->placeholder('-'),
            ])
            // --- FILTRI STRATEGICI PER LA COMPLIANCE ---
            ->filters([
                // Filtro immediato per Stato
                SelectFilter::make('status')
                    ->label('Stato Avanzamento')
                    ->options(AuditStatus::class),
                // Filtro per Origine dell'Audit
                SelectFilter::make('origin_type')
                    ->label('Direzione Audit')
                    ->options([
                        'internal' => 'Interno',
                        'incoming' => 'In Entrata (Subìto)',
                        'outgoing' => 'In Uscita (Effettuato)',
                    ]),
                // Filtro temporale avanzato per beccare gli audit in ritardo
                Filter::make('in_ritardo')
                    ->label('Mostra solo in ritardo')
                    ->query(fn(Builder $query): Builder => $query
                        ->whereNull('executed_at')
                        ->where('scheduled_at', '<', now()->startOfDay())),
            ])
            // --- AZIONI SULLA RIGA ---
            ->actions([
                // ViewAction::make(),
                EditAction::make(),
            ])
            // --- AZIONI DI MASSA ---
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
