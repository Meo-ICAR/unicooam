<?php

namespace App\Filament\Resources\OamSemestrales\Tables;

use App\Filament\Exports\DynamicGroupExport;
use App\Filament\Resources\OamPratiches\OamPraticheResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;

class OamSemestralesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->selectable(false)
            ->reorderableColumns()
            ->recordActionsPosition(RecordActionsPosition::BeforeColumns)
            ->columns([
                TextColumn::make('prodotto_creditizio')
                    ->sortable()
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
            ])
            ->filters([
                //
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
                            'prodotto_creditizio' => ['values' => [0 => $record->prodotto_creditizio]],
                        ],
                    ])),
            ]);
    }
}
