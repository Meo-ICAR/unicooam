<?php

namespace App\Filament\Resources\OamPratiches\Pages;

use App\Filament\Resources\OamPratiches\OamPraticheResource;
use App\Models\PROFORMA\Clienti;
use App\Models\PROFORMA\Pratica;
use App\Models\PROFORMA\Provvigione;
use App\Services\OamSemestraleService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
// Usa Filament\Tables\Actions\Action se sei in una tabella
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

// CORRETTO

class ListOamPratiches extends ListRecords
{
    protected static string $resource = OamPraticheResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //  CreateAction::make(),
            Action::make('stornoProvvigione')
                ->label('Storno Provvigioni')
                ->icon('heroicon-o-minus')
                ->color('danger')
                ->modalHeading('Registra uno Storno Provvigionale')
                ->modalSubmitActionLabel('Conferma e Storna')
                ->form([
                    // 1. Selezione dell'Istituto
                    Select::make('istituto_id')
                        ->label('Istituto di Credito')
                        ->options(
                            Clienti::query()
                                ->whereNotNull('name')
                                ->where('name', '!=', '')
                                ->pluck('name', 'id')
                        )
                        ->required()
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function (Set $set) {
                            $set('id_pratica', null);
                            $set('importo_storno', null);
                        }),

                    // 2. Selezione della Pratica (Erogata e non stornata)
                    Select::make('id_pratica')
                        ->label('Cliente / Pratica da stornare')

                        ->placeholder(fn (Get $get) => $get('istituto_id') ? 'Seleziona la pratica...' : 'Prima seleziona un istituto')
                        ->disabled(fn (Get $get) => ! $get('istituto_id'))
                        ->required()
                        ->searchable()
                        ->live()
                        ->options(function (Get $get) {
                            $istitutoId = $get('istituto_id');

                            if (! $istitutoId) {
                                return [];
                            }

                            return Pratica::query()
                                ->whereHas('istituto', function ($query) use ($istitutoId) {
                                    $query->where('id', $istitutoId);
                                })
                                ->whereNotNull('erogated_at')
                                ->whereDoesntHave('provvigioni', function ($query) {
                                    $query->where('status_compenso', 'Pratica stornata');
                                })
                                ->get()
                                ->mapWithKeys(function ($pratica) {
                                    $nomeCliente = $pratica->cliente
                                        ? "{$pratica->cliente->cognome} {$pratica->cliente->nome}"
                                        : ($pratica->nome_cliente ?? "Pratica #{$pratica->id}");

                                    return [$pratica->id => "{$nomeCliente} (Pratica #{$pratica->id})"];
                                });
                        })
                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                            if ($state) {
                                // Precompila l'importo dello storno sommando le provvigioni originarie in uscita
                                $totaleUscite = Provvigione::where('id_pratica', $state)
                                    ->where('entrata_uscita', 'Uscita')
                                    ->where('stato', '!=', 'Annullato')
                                    ->where('iscliente', '!=', true)
                                    ->sum('importo');

                                $set('importo_storno', $totaleUscite);
                            } else {
                                $set('importo_storno', null);
                            }
                        }),

                    // 3. Data dello Storno
                    DatePicker::make('data_storno')
                        ->label('Data dello Storno')
                        ->required()
                        ->default(now())
                        ->native(false)
                        ->displayFormat('d/m/Y'),

                    // 4. Importo dello Storno (Sostituisce la percentuale)
                    TextInput::make('importo_storno')
                        ->label('Importo Storno')
                        ->numeric()
                        ->prefix('€')
                        ->required(),

                    // 5. Note Opzionali
                    Textarea::make('note')
                        ->label('Motivo dello storno')
                        ->placeholder('Es. Recesso cliente nei termini, insoluto, ecc.')
                        ->columnSpanFull(),
                ])
                ->action(function (array $data) {
                    $idPratica = $data['id_pratica'];
                    $dataStorno = $data['data_storno'];
                    $importoStornoRichiesto = (float) $data['importo_storno'];

                    // Recupera le provvigioni di 'Uscita' per la pratica
                    $relatedUscite = Provvigione::where('id_pratica', $idPratica)
                        ->where('entrata_uscita', 'Entrata')
                        ->where('stato', '!=', 'Annullato')
                        ->where('iscliente', '!=', true)
                        ->get();

                    if ($relatedUscite->isEmpty()) {
                        Notification::make()
                            ->title('Nessun record trovato')
                            ->body('Non sono state trovate provvigioni di tipo "Entrata" valide da stornare per questa pratica.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $totaleOriginale = $relatedUscite->sum('importo');

                    // Rapporto di storno (gestisce sia lo storno totale che parziale)
                    $ratio = $totaleOriginale > 0 ? ($importoStornoRichiesto / $totaleOriginale) : 1;

                    foreach ($relatedUscite as $uscita) {
                        $newRecord = $uscita->replicate();

                        $importoSingoloStorno = $uscita->importo * $ratio;

                        $newRecord->status_compenso = 'Pratica stornata';
                        $newRecord->importo = -abs($importoSingoloStorno); // Salvato in negativo
                        $newRecord->descrizione = 'Storno provvigione '.($data['note'] ? ' - '.$data['note'] : '');

                        $newRecord->data_inserimento_compenso = $dataStorno;
                        $newRecord->data_status = $dataStorno;
                        $newRecord->erogated_at = $dataStorno;

                        $newRecord->data_pagamento = null;
                        $newRecord->stato = 'Inserito';
                        $newRecord->n_fattura = null;
                        $newRecord->data_fattura = null;
                        $newRecord->status_pagamento = 'Inserito';
                        $newRecord->proforma_id = null;

                        $newRecord->save();

                        // Aggiorna la quota sul record originario
                        $uscita->update([
                            'quota' => $importoSingoloStorno,
                            // 'storned_at' => $dataStorno,
                        ]);
                    }

                    Notification::make()
                        ->title('Storno Registrato')
                        ->body('Registrato storno di € '.number_format($importoStornoRichiesto, 2, ',', '.')." per la Pratica #{$idPratica}.")
                        ->success()
                        ->send();
                }),
            Action::make('rigeneraReport')
                ->label('Ricalcola Aggregati Semestrali')
                ->icon('heroicon-o-calculator')
                ->action(function (OamSemestraleService $service) {
                    $count = $service->aggregate();

                    Notification::make()
                        ->title('Report Aggiornato')
                        ->body("I dati semestrali sono stati ricalcolati con successo ({$count} righe elaborate).")
                        ->success()
                        ->send();
                }),
        ];
    }
}
