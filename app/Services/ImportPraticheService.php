<?php

namespace App\Services;

use App\Models\PROFORMA\Clienti;
use App\Models\PROFORMA\Fornitore;
use App\Models\PROFORMA\Pratica;
use App\Models\PROFORMA\Provvigione;
use App\Models\OamPratiche;
use App\Services\OamSemestraleService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class ImportPraticheService
{
    /**
     * Importa i dati da Pratica a OamPratiche
     *
     * @param Carbon $startAt Data di inizio
     * @param Carbon $endAt Data di fine
     * @return int Numero di record importati
     */
    public function import(Carbon $startAt, Carbon $endAt): int
    {
        // Svuota la tabella prima di ogni nuova esecuzione
        OamPratiche::truncate();

        $importedCount = 0;

        $query = Pratica::query()
            ->whereNull('rejected_at')
            ->where('data_inserimento_pratica', '>=', '2025-01-01')
            ->where('stato_pratica', '<>', 'INSERITA')
            ->where('is_notowned', 0)
            ->whereNotIn('tipo_prodotto', ['Utenza', 'Polizza'])
            ->where(function ($query) use ($startAt, $endAt) {
                $query
                    ->whereNull('erogated_at')
                    ->orWhere(function ($q) use ($startAt, $endAt) {
                        $q
                            ->where('erogated_at', '>=', $startAt)
                            ->where('erogated_at', '<', $endAt);
                    });
            });

        $query->chunk(500, function (Collection $pratiche) use (&$importedCount, $startAt, $endAt) {
            DB::transaction(function () use ($pratiche, &$importedCount, $startAt, $endAt) {
                foreach ($pratiche as $pratica) {
                    $this->importSingle($pratica, $startAt, $endAt);
                    $importedCount++;
                }
            });
        });

        DB::update('UPDATE oam_pratiches o
        INNER JOIN oam_codes c ON c.tipo_prodotto = o.tipo_prodotto
        SET
            o.prodotto_creditizio =  c.description,
            o.pratiche_lavorazione = IF(o.erogated_at IS NULL, 1, 0),
            o.pratiche_intermediate = IF(o.erogated_at IS NOT NULL, 1, 0); ');

        DB::update("UPDATE oam_pratiches o

        SET
            o.prodotto_creditizio =
            IF(o.tipo_prodotto = 'Mutuo','Segnalazione Mutuo',o.prodotto_creditizio)

            where (o.tipo_prodotto = 'Mutuo') and (not (o.istituto like '%banca%') or (o.istituto is null))");

        DB::update('UPDATE oam_pratiches o
            SET
             o.erogato_lavorazione = o.erogato_lordo,
             o.erogato_lordo = 0
            where o.pratiche_lavorazione = 1');

        $service = new OamSemestraleService();
        $count = $service->aggregate();

        return $importedCount;
    }

    /**
     * Importa una singola pratica
     *
     * @param Pratica $pratica
     * @return OamPratiche
     */
    public function importSingle(Pratica $pratica, Carbon $startAt, Carbon $endAt): OamPratiche
    {
        $company_id = '45d36df8-369f-40ce-b4fd-b5907c342fe9';
        $period = $startAt->format('Ym');

        $erogato = $pratica->net;

        $rejected = $pratica->rejected_at;

        $cliente = trim(($pratica->nome_cliente ?? '') . ' '
            . ($pratica->cognome_cliente ?? ''));
        $erogated = $pratica->erogated_at;
        $approved = $pratica->approved_at;
        if ($approved == null) {
            $approved = $erogated;
        }
        $sended = $pratica->sended_at;
        if ($sended == null) {
            $sended = $approved;
        }
        $istitutox = $pratica->denominazione_banca;
        $istituto = Clienti::getClienteNomeByName($istitutox);
        $agente = Fornitore::getFornitoreNomeByName($pratica->denominazione_agente);
        $id_pratica = $pratica->codice_pratica;
        $provv_clientela = Provvigione::getProvvigioneCliente($id_pratica);
        $provv_clientela = Provvigione::getProvvigioneCliente($id_pratica);
        $provv_istituto_comp = Provvigione::getProvvigioneIstituto($id_pratica, $istitutox);
        $premi_istituto_comp = Provvigione::getPremioIstituto($id_pratica, $istitutox);
        $payout_rete_credito = Provvigione::getProvvigioneAgenti($id_pratica);
        $storno = 0;  // Provvigione::getProvvigioneStorno($id_pratica);

        return OamPratiche::updateOrCreate(
            [
                // Usiamo il codice pratica come chiave di identificazione per non duplicare i record
                'pratica' => $pratica->codice_pratica,
            ],
            [
                'company_id' => $company_id,
                'period' => $period,
                'istituto' => $istituto,
                'intermediari_non_convenzionati' => $istituto > 'A' ? 0 : 1,
                'agente' => $agente,
                'cliente' => $cliente ?: null,
                'tipo_prodotto' => $pratica->tipo_prodotto,
                'erogato_lordo' => $erogato,
                'sended_at' => $sended,
                'approved_at' => $approved,
                'erogated_at' => $erogated,
                'rejected_at' => $rejected,
                'provv_clientela' => $provv_clientela,
                'provv_istituto_comp' => $provv_istituto_comp,
                'premi_istituto_comp' => $premi_istituto_comp,
                'payout_rete_credito' => $payout_rete_credito,
                'importo_retrocesse' => $storno,
                'num_rivalse' => $storno <> 0 ? 1 : 0,
            ]
        );
    }
}
