<?php

namespace App\Filament\Resources\OamPratiches\Tables;

use App\Filament\Exports\DynamicGroupExport;
use App\Models\OamPratiche;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\ExportAction;

class OamPratichesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tipo_prodotto')
                    ->searchable(),
                TextColumn::make('istituto')
                    ->searchable(),
                TextColumn::make('agente')
                    ->searchable(),
                TextColumn::make('cliente')
                    ->searchable(),
                TextColumn::make('sended_at')
                    ->label('Inviata')
                    ->date()
                    ->sortable(),
                TextColumn::make('approved_at')
                    ->label('Approvata')
                    ->date()
                    ->sortable(),
                TextColumn::make('erogated_at')
                    ->label('Erogata')
                    ->date()
                    ->sortable(),
                TextColumn::make('rejected_at')
                    ->label('Rifiutata')
                    ->date()
                    ->sortable(),
                TextColumn::make('storned_at')
                    ->label('Stornata')
                    ->date()
                    ->sortable(),
                TextColumn::make('pratica')
                    ->searchable(),
                TextColumn::make('prodotto_creditizio')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('tipo_prodotto')
                    ->label('Prodotto')
                    ->searchable(),
                TextColumn::make('intermediari_convenzionati')
                    ->label('Convenzionati')
                    ->numeric()
                    ->alignRight()
                    ->sortable(),
                TextColumn::make('intermediari_non_convenzionati')
                    ->label('Non Convenz.')
                    ->numeric()
                    ->alignRight()
                    ->sortable(),
                TextColumn::make('pratiche_intermediate')
                    ->label('Intermedie')
                    ->numeric()  // Formatta automaticamente come € 1.234,56
                    ->alignRight()  // Allinea a destra per una lettura migliore
                    ->sortable(),
                TextColumn::make('pratiche_lavorazione')
                    ->label('Lavorazione')
                    ->numeric()  // Formatta automaticamente come € 1.234,56
                    ->alignRight()  // Allinea a destra per una lettura migliore
                    ->sortable(),
                TextColumn::make('erogato_lordo')
                    ->label('Erogato')
                    ->money('EUR')  // Formatta automaticamente come € 1.234,56
                    ->alignRight()  // Allinea a destra per una lettura migliore
                    ->sortable(),
                TextColumn::make('erogato_lavorazione')
                    ->label('Erogato Lav.')
                    ->money('EUR')  // Formatta automaticamente come € 1.234,56
                    ->alignRight()  // Allinea a destra per una lettura migliore
                    ->sortable(),
                TextColumn::make('provv_clientela')
                    ->label('Provv. Clientela')
                    ->money('EUR')  // Formatta automaticamente come € 1.234,56
                    ->alignRight()  // Allinea a destra per una lettura migliore
                    ->sortable(),
                TextColumn::make('provv_istituto_comp')
                    ->label('Provvigioni')
                    ->money('EUR')  // Formatta automaticamente come € 1.234,56
                    ->alignRight()  // Allinea a destra per una lettura migliore
                    ->sortable(),
                TextColumn::make('premi_istituto_comp')
                    ->label('Premi')
                    ->money('EUR')  // Formatta automaticamente come € 1.234,56
                    ->alignRight()  // Allinea a destra per una lettura migliore
                    ->sortable(),
                TextColumn::make('payin_ass_banche')
                    ->label('Ass. Banche')
                    ->money('EUR')  // Formatta automaticamente come € 1.234,56
                    ->alignRight()  // Allinea a destra per una lettura migliore
                    ->sortable(),
                TextColumn::make('payin_ass_broker')
                    ->label('Broker')
                    ->money('EUR')  // Formatta automaticamente come € 1.234,56
                    ->alignRight()  // Allinea a destra per una lettura migliore
                    ->sortable(),
                TextColumn::make('payin_ass_broker_cap')
                    ->label('Captive')
                    ->money('EUR')  // Formatta automaticamente come € 1.234,56
                    ->alignRight()  // Allinea a destra per una lettura migliore
                    ->sortable(),
                TextColumn::make('payout_rete_credito')
                    ->label('Rete Credito')
                    ->money('EUR')  // Formatta automaticamente come € 1.234,56
                    ->alignRight()  // Allinea a destra per una lettura migliore
                    ->sortable(),
                TextColumn::make('payout_rete_ass_banche')
                    ->label('Rete Ass. Banche')
                    ->money('EUR')  // Formatta automaticamente come € 1.234,56
                    ->alignRight()  // Allinea a destra per una lettura migliore
                    ->sortable(),
                TextColumn::make('payout_rete_ass_broker')
                    ->label('Rete Ass. Broker')
                    ->money('EUR')  // Formatta automaticamente come € 1.234,56
                    ->alignRight()  // Allinea a destra per una lettura migliore
                    ->sortable(),
                TextColumn::make('payout_rete_ass_broker_cap')
                    ->label('Rete Captive')
                    ->money('EUR')  // Formatta automaticamente come € 1.234,56
                    ->alignRight()  // Allinea a destra per una lettura migliore
                    ->sortable(),
                TextColumn::make('num_rivalse')
                    ->label('Rivalse')
                    ->numeric()  // Formatta automaticamente come € 1.234,56
                    ->alignRight()  // Allinea a destra per una lettura migliore
                    ->sortable(),
                TextColumn::make('importo_retrocesse')
                    ->label('Retrocesse')
                    ->money('EUR')  // Formatta automaticamente come € 1.234,56
                    ->alignRight()  // Allinea a destra per una lettura migliore
                    ->sortable(),
                TextColumn::make('pratica')
                    ->searchable(),
                TextColumn::make('sended_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('approved_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('erogated_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('rejected_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('storned_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                // TrashedFilter::make(),
                // Dentro il metodo ->filters([...]) della tua tabella:
                SelectFilter::make('prodotto_creditizio')
                    ->label('Prodotto Creditizio')
                    ->multiple()
                    ->searchable()
                    ->options(fn() => OamPratiche::query()  // Recupera automaticamente il Model di questa Resource (es. OamSemestrale o OamPratiche)
                        ->distinct()
                        ->orderBy('prodotto_creditizio')  // Opzionale: ordina alfabeticamente se vuoi
                        ->pluck('prodotto_creditizio', 'prodotto_creditizio')
                        ->toArray()),
                SelectFilter::make('tipo_prodotto')
                    ->label('Prodotto')
                    ->multiple()
                    ->searchable()
                    ->options(fn() => OamPratiche::query()  // Recupera automaticamente il Model di questa Resource (es. OamSemestrale o OamPratiche)
                        ->whereNotNull('tipo_prodotto')
                        ->where('tipo_prodotto', '!=', '')  // Evita stringhe vuote
                        ->distinct()
                        ->orderBy('tipo_prodotto')  // Opzionale: ordina alfabeticamente se vuoi
                        ->pluck('tipo_prodotto', 'tipo_prodotto')
                        ->toArray()),
                SelectFilter::make('istituto')
                    ->label('Istituto')
                    ->multiple()
                    ->searchable()
                    ->options(fn() => OamPratiche::query()  // Recupera automaticamente il Model di questa Resource (es. OamSemestrale o OamPratiche)
                        ->whereNotNull('istituto')
                        ->where('istituto', '!=', '')  // Evita stringhe vuote
                        ->distinct()
                        ->orderBy('istituto')  // Opzionale: ordina alfabeticamente se vuoi
                        ->pluck('istituto', 'istituto')
                        ->toArray()),
                Filter::make('importo_retrocesse')
                    ->label('Stornate')
                    ->query(fn(Builder $query): Builder => $query->where('importo_retrocesse', '!=', 0)),
                Filter::make('intermediari_non_convenzionati')
                    ->label('Intermediari Non Convenzionati')
                    ->query(fn(Builder $query): Builder => $query->where('intermediari_non_convenzionati', '=', 1)),
                SelectFilter::make('agente')
                    ->label('Agente')
                    ->multiple()
                    ->searchable()
                    ->options(fn() => OamPratiche::query()  // Recupera automaticamente il Model di questa Resource (es. OamSemestrale o OamPratiche)
                        ->whereNotNull('agente')
                        ->where('agente', '!=', '')  // Evita stringhe vuote
                        ->distinct()
                        ->orderBy('agente')  // Opzionale: ordina alfabeticamente se vuoi
                        ->pluck('agente', 'agente')
                        ->toArray()),
            ], layout: FiltersLayout::AboveContent)
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
