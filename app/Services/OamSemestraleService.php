<?php

namespace App\Services;

use App\Models\PROFORMA\Provvigione;
use App\Models\OamPratiche;
use App\Models\OamSemestrale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OamSemestraleService
{
    /**
     * Aggrega le pratiche OAM e le importa in OamSemestrale
     *
     * @return int Numero di record aggregati salvati
     */
    public function aggregate(string $period = null): int
    {
        // 1. Svuotiamo prima di aprire la transazione
        if (!$period) {
            OamSemestrale::truncate();
        } else {
            OamSemestrale::where('period', $period)->delete();
        }
        return DB::transaction(function () use ($period) {
            // Svuota la tabella prima di ricostruire gli aggregati

            /*
             * $deleteQuery = OamSemestrale::query();
             * if ($period) {
             *     $deleteQuery->where('period', $period);
             * }
             * $deleteQuery->delete();
             */

            $aggregates = OamPratiche::query()
                ->select('company_id', 'period', 'prodotto_creditizio')
                ->selectRaw('SUM(intermediari_convenzionati) as intermediari_convenzionati')
                ->selectRaw('SUM(intermediari_non_convenzionati) as intermediari_non_convenzionati')
                ->selectRaw('SUM(pratiche_intermediate) as pratiche_intermediate')
                ->selectRaw('SUM(pratiche_lavorazione) as pratiche_lavorazione')
                ->selectRaw('SUM(num_rivalse) as num_rivalse')
                ->selectRaw('SUM(erogato_lordo) as erogato_lordo')
                ->selectRaw('SUM(erogato_lavorazione) as erogato_lavorazione')
                ->selectRaw('SUM(provv_clientela) as provv_clientela')
                ->selectRaw('SUM(provv_istituto_comp) as provv_istituto_comp')
                ->selectRaw('SUM(premi_istituto_comp) as premi_istituto_comp')
                ->selectRaw('SUM(payin_ass_banche) as payin_ass_banche')
                ->selectRaw('SUM(payin_ass_broker) as payin_ass_broker')
                ->selectRaw('SUM(payin_ass_broker_cap) as payin_ass_broker_cap')
                ->selectRaw('SUM(payout_rete_credito) as payout_rete_credito')
                ->selectRaw('SUM(payout_rete_ass_banche) as payout_rete_ass_banche')
                ->selectRaw('SUM(payout_rete_ass_broker) as payout_rete_ass_broker')
                ->selectRaw('SUM(payout_rete_ass_broker_cap) as payout_rete_ass_broker_cap')
                ->selectRaw('SUM(importo_retrocesse) as importo_retrocesse')
                ->groupBy('company_id', 'period', 'prodotto_creditizio')
                ->orderBy('company_id')
                ->orderBy('period')
                ->orderBy('prodotto_creditizio')
                ->get();

            foreach ($aggregates as $row) {
                $company_id = $row->company_id;
                $period = $row->period;
                OamSemestrale::create([
                    'company_id' => $row->company_id,
                    'period' => $row->period,
                    'prodotto_creditizio' => $row->prodotto_creditizio,
                    'num_rivalse' => $row->num_rivalse ?? 0,
                    'importo_retrocesse' => $row->importo_retrocesse ?? 0.0,
                    'intermediari_convenzionati' => $row->intermediari_convenzionati ?? 0,
                    'intermediari_non_convenzionati' => $row->intermediari_non_convenzionati ?? 0,
                    'pratiche_intermediate' => $row->pratiche_intermediate ?? 0,
                    'pratiche_lavorazione' => $row->pratiche_lavorazione ?? 0,
                    'erogato_lordo' => $row->erogato_lordo ?? 0.0,
                    'erogato_lavorazione' => $row->erogato_lavorazione ?? 0.0,
                    'provv_clientela' => $row->provv_clientela ?? 0.0,
                    'provv_istituto_comp' => $row->provv_istituto_comp ?? 0.0,
                    'premi_istituto_comp' => $row->premi_istituto_comp ?? 0.0,
                    'payin_ass_banche' => $row->payin_ass_banche ?? 0.0,
                    'payin_ass_broker' => $row->payin_ass_broker ?? 0.0,
                    'payin_ass_broker_cap' => $row->payin_ass_broker_cap ?? 0.0,
                    'payout_rete_credito' => $row->payout_rete_credito ?? 0.0,
                    'payout_rete_ass_banche' => $row->payout_rete_ass_banche ?? 0.0,
                    'payout_rete_ass_broker' => $row->payout_rete_ass_broker ?? 0.0,
                    'payout_rete_ass_broker_cap' => $row->payout_rete_ass_broker_cap ?? 0.0,
                ]);
            }

            $risultati = Provvigione::query()
                ->with([
                    // 1. Carica la relazione 'pratica'
                    'pratica' => function ($query) {
                        $query->select('id', 'tipo_prodotto');
                    },
                    // 2. Carica la relazione 'oamCode' DENTRO 'pratica' (Passando dal punto)
                    'pratica.oamCode' => function ($query) {
                        // Selezioniamo 'tipo_prodotto' (serve a Laravel per il match) e i campi extra che vuoi recuperare
                        $query->select('tipo_prodotto', 'description');  // Sostituisci con i campi reali di oam_codes
                    }
                ])
                ->where('descrizione', 'like', '%storno%')
                ->where('tipo', 'Istituto')
                ->get()
                ->map(function ($provvigione) {
                    // Estraiamo in sicurezza l'oggetto OamCode se esiste
                    $oamCode = $provvigione->pratica?->oamCode;

                    return (object) [
                        'id_pratica' => $provvigione->id_pratica,
                        'tipo' => $provvigione->tipo,
                        'data_status' => $provvigione->data_status,
                        'importo' => -$provvigione->importo,
                        // Dati dal DB Pratiche
                        'tipo_prodotto' => $provvigione->pratica?->tipo_prodotto,
                        // Dati dal terzo DB (OamCode) tramite Pratica
                        'oam_descrizione' => $oamCode?->description,  // Es. "Mutuo Ipotecario"
                    ];
                });

            // Scrive nel log una riga di info con i dati strutturati
            Log::info('Risultati Storni Istituto:', [
                'totale_record' => $risultati->count(),
                'dati' => $risultati->toArray()
            ]);

            // Raggruppa per descrizione e mappa i dati calcolando somma e conteggio
            $reportStorni = $risultati->groupBy('oam_descrizione')->map(function ($gruppo) {
                return [
                    'importo_retrocesse' => $gruppo->sum('importo'),
                    'num_rivalse' => $gruppo->count(),  // Questo fa il lavoro del count(*)
                ];
            });

            // Scrive il report finale nel log
            Log::info('Report Storni OAM (Somma e Conteggio):', $reportStorni->toArray());

            // Cicliamo sulla collection ottenendo sia la chiave (prodotto) che i dati associati
            foreach ($reportStorni as $prodottoCreditizio => $datiStorno) {
                OamSemestrale::updateOrCreate(
                    // 1. Criteri di ricerca univoci per questa riga del ciclo
                    [
                        'company_id' => $company_id,
                        'period' => $period,
                        'prodotto_creditizio' => $prodottoCreditizio,  // Usa la chiave corrente del ciclo
                    ],
                    // 2. I dati dinamici presi dall'elemento corrente
                    [
                        // Mappiamo il conteggio sul tuo campo 'num_rivalse'
                        'num_rivalse' => $datiStorno['num_rivalse'] ?? 0,
                        // Mappiamo l'importo totale sul tuo campo 'importo_retrocesse'
                        'importo_retrocesse' => $datiStorno['importo_retrocesse'] ?? 0.0,
                    ]
                );
            }

            return $aggregates->count();
        });
    }
}
