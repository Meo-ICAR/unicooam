<?php

namespace App\Filament\Resources\OamSemestrales\Tables;

use App\Filament\Exports\DynamicGroupExport;
use App\Filament\Resources\OamPratiches\OamPraticheResource;
use App\Models\OamSemestrale;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;

class OamSemestralesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->selectable(false)
            ->defaultPaginationPageOption(50)
            ->defaultSort('abi_name')
            ->reorderableColumns()
            ->recordActionsPosition(RecordActionsPosition::BeforeColumns)
            ->groups([
                Group::make('abi_name')
                    ->label('Finanziatore')
                    ->titlePrefixedWithLabel(false)
                  //  ->getTitleFromRecordUsing(fn (OamSemestrale $record): string => $record->abi_name)
                    ->collapsible(),
                Group::make('prodotto_creditizio')
                  //  ->label('Finanziatore')
                    ->titlePrefixedWithLabel(false)
                  //  ->getTitleFromRecordUsing(fn (OamSemestrale $record): string => $record->abi_name)
                    ->collapsible(),
            ])
            ->columns([
                TextColumn::make('abi_name')
                    ->label('Finanziatore')->sortable()
                    ->searchable(),

                TextColumn::make('prodotto_creditizio')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('pratiche_intermediate')
                    ->label('Intermediate')
                    ->numeric()  // Formatta automaticamente come € 1.234,56
                    ->alignRight()  // Allinea a destra per una lettura migliore

                    ->summarize(Sum::make()->label(''))

                    ->sortable(),
                TextColumn::make('pratiche_lavorazione')
                    ->label('Lavorazione')
                    ->numeric()  // Formatta automaticamente come € 1.234,56
                    ->alignRight()  // Allinea a destra per una lettura migliore

                    ->summarize(Sum::make()->label(''))
                    ->sortable(),
                TextColumn::make('erogato_lordo')
                    ->label('Erogato')
                    ->money('EUR')  // Formatta automaticamente come € 1.234,56
                    ->alignRight()  // Allinea a destra per una lettura migliore
                    ->money('EUR')  // Forza Euro e formato italiano
                    ->summarize(Sum::make()->money('EUR')->label(''))
                    ->sortable(),
                TextColumn::make('erogato_lavorazione')
                    ->label('Erogato Lav.')
                    ->money('EUR')  // Formatta automaticamente come € 1.234,56
                    ->alignRight()  // Allinea a destra per una lettura migliore
                    ->summarize(Sum::make()->money('EUR')->label(''))
                    ->sortable(),
                TextColumn::make('provv_clientela')
                    ->label('Provv. Clientela')
                    ->money('EUR')  // Formatta automaticamente come € 1.234,56
                    ->alignRight()  // Allinea a destra per una lettura migliore
                    ->summarize(Sum::make()->money('EUR')->label(''))
                    ->sortable(),
                TextColumn::make('provv_istituto_comp')
                    ->label('Provvigioni')
                    ->money('EUR')  // Formatta automaticamente come € 1.234,56
                    ->alignRight()  // Allinea a destra per una lettura migliore
                    ->summarize(Sum::make()->money('EUR')->label(''))
                    ->sortable(),
                TextColumn::make('premi_istituto_comp')
                    ->label('Premi')
                    ->money('EUR')  // Formatta automaticamente come € 1.234,56
                    ->alignRight()  // Allinea a destra per una lettura migliore
                    ->summarize(Sum::make()->money('EUR')->label(''))
                    ->sortable(),
                TextColumn::make('payin_ass_banche')
                    ->label('Ass. Banche')
                    ->money('EUR')  // Formatta automaticamente come € 1.234,56
                    ->summarize(Sum::make()->money('EUR')->label(''))
                    ->alignRight()  // Allinea a destra per una lettura migliore
                    ->sortable(),
                TextColumn::make('payin_ass_broker')
                    ->label('Broker')
                    ->money('EUR')  // Formatta automaticamente come € 1.234,56
                    ->summarize(Sum::make()->money('EUR')->label(''))
                    ->alignRight()  // Allinea a destra per una lettura migliore
                    ->sortable(),
                TextColumn::make('payin_ass_broker_cap')
                    ->label('Captive')
                    ->money('EUR')  // Formatta automaticamente come € 1.234,56
                    ->summarize(Sum::make()->money('EUR')->label(''))
                    ->alignRight()  // Allinea a destra per una lettura migliore
                    ->sortable(),
                TextColumn::make('payout_rete_credito')
                    ->label('Rete Credito')
                    ->money('EUR')  // Formatta automaticamente come € 1.234,56
                    ->summarize(Sum::make()->money('EUR')->label(''))
                    ->alignRight()  // Allinea a destra per una lettura migliore
                    ->sortable(),
                TextColumn::make('payout_rete_ass_banche')
                    ->label('Rete Ass. Banche')
                    ->money('EUR')  // Formatta automaticamente come € 1.234,56
                    ->summarize(Sum::make()->money('EUR')->label(''))
                    ->alignRight()  // Allinea a destra per una lettura migliore
                    ->sortable(),
                TextColumn::make('payout_rete_ass_broker')
                    ->label('Rete Ass. Broker')
                    ->money('EUR')  // Formatta automaticamente come € 1.234,56
                    ->summarize(Sum::make()->money('EUR')->label(''))
                    ->alignRight()  // Allinea a destra per una lettura migliore
                    ->sortable(),
                TextColumn::make('payout_rete_ass_broker_cap')
                    ->label('Rete Captive')
                    ->money('EUR')  // Formatta automaticamente come € 1.234,56
                    ->summarize(Sum::make()->money('EUR')->label(''))
                    ->alignRight()  // Allinea a destra per una lettura migliore
                    ->sortable(),
                TextColumn::make('num_rivalse')
                    ->label('Rivalse')
                    ->numeric()  // Formatta automaticamente come € 1.234,56

                    ->alignRight()  // Allinea a destra per una lettura migliore

                    ->summarize(Sum::make()->label(''))
                    ->sortable(),
                TextColumn::make('importo_retrocesse')
                    ->label('Retrocesse')
                    ->money('EUR')  // Formatta automaticamente come € 1.234,56
                    ->alignRight()  // Allinea a destra per una lettura migliore
                    ->summarize(Sum::make()->money('EUR')->label(''))

                    ->sortable(),
                TextColumn::make('is_convenzione')
                    ->label('Convenzione')
                    ->formatStateUsing(fn ($state) => $state ? 'SI' : 'NO')
                    ->sortable(),
                TextColumn::make('gestione')
                    ->label('Gestione')
                    ->sortable()
                    ->searchable(),

            ])
            ->filters([
                SelectFilter::make('abi_name')
                    ->label('Finanziatore')
                    ->multiple()
                    ->searchable()
                    ->options(fn () => OamSemestrale::query()  // Recupera automaticamente il Model di questa Resource (es. OamSemestrale o OamPratiche)
                        ->distinct()
                        ->whereNotNull('abi_name')            // <-- ESCLUDE I NULL
                        ->where('abi_name', '!=', '')
                        ->orderBy('abi_name')  // Opzionale: ordina alfabeticamente se vuoi
                        ->pluck('abi_name', 'abi_name')
                        ->toArray()),
                SelectFilter::make('prodotto_creditizio')
                    ->label('Prodotto Creditizio')
                    ->multiple()
                    ->searchable()
                    ->options(fn () => OamSemestrale::query()  // Recupera automaticamente il Model di questa Resource (es. OamSemestrale o OamPratiche)
                        ->whereNotNull('prodotto_creditizio') // <-- ESCLUDE I NULL
                        ->where('prodotto_creditizio', '!=', '') // <-- ESCLUDE LE STRINGHE VUOTE
                        ->distinct()
                        ->orderBy('prodotto_creditizio')  // Opzionale: ordina alfabeticamente se vuoi
                        ->pluck('prodotto_creditizio', 'prodotto_creditizio')
                        ->toArray()),
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
                // EditAction::make(),
                Action::make('view_pratiche')
                    ->label(false)
                    ->icon('heroicon-o-magnifying-glass-plus')
                    ->color('info')
                    ->url(fn ($record) => OamPraticheResource::getUrl('index', [
                        'filters' => [
                            // 'company_id' => ['value' => $record->company_id],
                            //  'period' => ['value' => $record->period],
                            'abi_name' => ['values' => [0 => $record->abi_name]],
                            'prodotto_creditizio' => ['values' => [0 => $record->prodotto_creditizio]],
                        ],
                    ])),
            ]);
    }
}
