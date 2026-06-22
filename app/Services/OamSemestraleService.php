<?php

namespace App\Services;

use App\Models\OamPratiche;
use App\Models\OamSemestrale;
use App\Models\PROFORMA\Provvigione;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OamSemestraleService
{
    /**
     * Aggrega le pratiche OAM e le importa in OamSemestrale.
     *
     * @param  string|null  $period  Periodo nel formato YYYYMM (es. 202501). Se null, riaggrega tutto.
     * @param  string|null  $companyId  UUID della company. Se null, usa la prima company.
     * @return int Numero di record aggregati salvati
     */
    public function aggregate(?string $period = null, ?string $companyId = null): int
    {
        // Svuotiamo prima di aprire la transazione
        if (! $period) {
            OamSemestrale::truncate();
        } else {
            OamSemestrale::where('period', $period)->delete();
        }

        return DB::transaction(function () use ($period, $companyId) {
            $query = OamPratiche::query()
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
                ->orderBy('prodotto_creditizio');

            if ($period) {
                $query->where('period', $period);
            }

            if ($companyId) {
                $query->where('company_id', $companyId);
            }

            $aggregates = $query->get();

            // Tracciamo il company_id e period dell'ultimo record aggregato
            // per usarli nel ciclo storni
            $lastCompanyId = $companyId;
            $lastPeriod = $period;

            foreach ($aggregates as $row) {
                $lastCompanyId = $row->company_id;
                $lastPeriod = $row->period;

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

            // Calcolo storni: provvigioni Istituto con "storno" in descrizione
            $risultati = Provvigione::query()
                ->with([
                    'pratica' => fn ($q) => $q->select('id', 'tipo_prodotto'),
                    'pratica.oamCode' => fn ($q) => $q->select('tipo_prodotto', 'description'),
                ])
                ->where('descrizione', 'like', '%storno%')
                ->where('tipo', 'Istituto')
                ->get()
                ->map(function ($provvigione) {
                    $oamCode = $provvigione->pratica?->oamCode;

                    return (object) [
                        'id_pratica' => $provvigione->id_pratica,
                        'importo' => -$provvigione->importo,
                        'oam_descrizione' => $oamCode?->description,
                    ];
                });

            Log::info('Risultati Storni Istituto:', [
                'totale_record' => $risultati->count(),
            ]);

            $reportStorni = $risultati->groupBy('oam_descrizione')->map(function ($gruppo) {
                return [
                    'importo_retrocesse' => $gruppo->sum('importo'),
                    'num_rivalse' => $gruppo->count(),
                ];
            });

            Log::info('Report Storni OAM (Somma e Conteggio):', $reportStorni->toArray());

            foreach ($reportStorni as $prodottoCreditizio => $datiStorno) {
                OamSemestrale::updateOrCreate(
                    [
                        'company_id' => $lastCompanyId,
                        'period' => $lastPeriod,
                        'prodotto_creditizio' => $prodottoCreditizio,
                    ],
                    [
                        'num_rivalse' => $datiStorno['num_rivalse'] ?? 0,
                        'importo_retrocesse' => $datiStorno['importo_retrocesse'] ?? 0.0,
                    ]
                );
            }

            return $aggregates->count();
        });
    }
}
