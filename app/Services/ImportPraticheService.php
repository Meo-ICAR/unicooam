<?php

namespace App\Services;

use App\Models\OamPratiche;
use App\Models\PROFORMA\Clienti;
use App\Models\PROFORMA\Fornitore;
use App\Models\PROFORMA\Pratica;
use App\Models\PROFORMA\Provvigione;
use App\ValueObjects\OamSemester;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

class ImportPraticheService
{
    /**
     * Importa i dati da Pratica a OamPratiche
     *
     * @param  Carbon  $startAt  Data di inizio
     * @param  Carbon  $endAt  Data di fine
     * @return int Numero di record importati
     */
    public function import(?Carbon $startAt = null, ?Carbon $endAt = null): int
    {

        // 1. Recuperiamo il semestre di default
        $defaultSemester = OamSemester::getInBaseAlMeseCorrente();

        // 2. INVECE di usare stdClass, CLONIAMO l'oggetto OamSemester
        $semesterAppoggio = clone $defaultSemester;

        // Sovrascriviamo le date solo se ne hai passata una personalizzata
        if ($startAt) {
            $semesterAppoggio->start = $startAt;
        }
        if ($endAt) {
            $semesterAppoggio->end = $endAt;
        }

        OamPratiche::truncate();

        $importedCount = 0;

        // 3. Passiamo il VERO oggetto OamSemester allo scope
        $query = Pratica::perSemestreOam($semesterAppoggio);

        $query->chunk(1000, function (Collection $pratiche) use (&$importedCount, $semesterAppoggio) {
            DB::transaction(function () use ($pratiche, &$importedCount, $semesterAppoggio) {
                foreach ($pratiche as $pratica) {

                    // Ora $semesterAppoggio è un OamSemester e PHP non darà più errore!
                    $this->importSingle($pratica, $semesterAppoggio);
                    $importedCount++;
                }
            });
        });

        // inserisci provvigioni di storno
        // Calcolo storni: provvigioni Istituto con "storno" in descrizione
        $storniCount = 0;
        // FIX 1: Passiamo la stringa del periodo (es. '202606') o le date corrette anziché l'oggetto intero
        // Se lo scope StorniOam vuole il periodo in formato stringa 'Ym':
        $periodString = '202606'; // $semesterAppoggio->end;

        $risultati = Provvigione::StorniOam($periodString)
            ->whereHas('pratica', function ($q) use ($semesterAppoggio) {
                // La pratica DEVE esistere e deve essere stata erogata prima dell'inizio del semestre
                $q->where('erogated_at', '<', $semesterAppoggio->start->copy()->startOfDay());
            })
            ->with([
                'pratica' => fn ($q) => $q->select('id', 'erogated_at', 'codice_pratica', 'denominazione_banca',
                    'denominazione_agente', 'tipo_prodotto', 'abi_name', 'net', 'nome_cliente', 'cognome_cliente'),
            ])
            ->get();

        foreach ($risultati as $provvigione) {
            $this->importStorno($provvigione->pratica, $provvigione->importo);
            $storniCount++;
        }
        //  Log::info('Report Storni OAM (Somma e Conteggio):', $reportStorni->toArray());

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

        $service = new OamSemestraleService;
        $count = $service->aggregate(null, companyId: app(CompanyResolver::class)->resolveId());

        return $importedCount;
    }

    /**
     * Importa una singola pratica
     */
    public function importSingle(Pratica $pratica, OamSemester $semester): OamPratiche
    {
        $company_id = app(CompanyResolver::class)->resolveId();

        // Recuperiamo il periodo dalla data di inizio del semestre
        $period = '202606'; // $semester->end;

        $erogato = $pratica->net;

        $rejected = $pratica->rejected_at;

        $cliente = trim(($pratica->nome_cliente ?? '').' '
            .($pratica->cognome_cliente ?? ''));

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
        $provv_istituto_comp = Provvigione::getProvvigioneIstituto($id_pratica, $istitutox);
        $premi_istituto_comp = Provvigione::getPremioIstituto($id_pratica, $istitutox);
        $payout_rete_credito = Provvigione::getProvvigioneAgenti($id_pratica);
        $storno = Provvigione::getProvvigioneStorno($id_pratica);

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
                'num_rivalse' => $storno != 0 ? 1 : 0,

                'abi_name' => $pratica->abi_name,
            ]
        );
    }

    /**
     * Importa una singola pratica
     */
    public function importStorno(Pratica $pratica, float $storno): OamPratiche
    {
        $company_id = app(CompanyResolver::class)->resolveId();

        // Recuperiamo il periodo dalla data di inizio del semestre
        //  $period = $semester->end->format('Ym');
        $period = '202606';
        $erogato = $pratica->net;

        $rejected = $pratica->rejected_at;

        $cliente = trim(($pratica->nome_cliente ?? '').' '
            .($pratica->cognome_cliente ?? ''));

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
        $agente = $pratica->denominazione_agente; // Fornitore::getFornitoreNomeByName($pratica->denominazione_agente);
        $id_pratica = $pratica->codice_pratica;

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
                'agente' => $pratica->denominazione_agente,
                'cliente' => $cliente ?: null,
                'tipo_prodotto' => $pratica->tipo_prodotto,
                'erogato_lordo' => 0,
                'sended_at' => $sended,
                'approved_at' => $approved,
                'erogated_at' => $erogated,
                'rejected_at' => $rejected,
                'provv_clientela' => 0,
                'provv_istituto_comp' => 0,
                'premi_istituto_comp' => 0,
                'payout_rete_credito' => 0,
                'importo_retrocesse' => $storno,
                'num_rivalse' => 1,
                'abi_name' => $pratica->abi_name,
            ]
        );
    }
}
