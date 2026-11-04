<?php

namespace App\Services;

use App\Models\OamPratiche;
use App\Models\PROFORMA\Clienti;
use App\ValueObjects\OamSemester;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ImportSentoviaService
{
    /** Stati Sentovia che equivalgono a pratica non ancora inserita (come INSERITA). */
    private const STATI_NON_INSERITI = ['Preventivo'];

    /** Stati Sentovia di pratica chiusa senza erogazione. */
    private const STATI_CHIUSI = ['Respinta', 'Rinuncia del cliente'];

    public function __construct(private readonly ImportPraticheService $praticheService) {}

    public static function defaultPratichePath(): string
    {
        return storage_path('app/private/sentovia/rinaldi_prestiti_pratiche.txt');
    }

    public static function defaultProvvigioniPath(): string
    {
        return storage_path('app/private/sentovia/rinaldi_prestiti_provvigioni.txt');
    }

    /**
     * Importa le pratiche del semestre dai file Sentovia (TSV) nella tabella
     * di lavoro oam_pratiches, al posto di pratiche e provvigioni del gestionale,
     * e ricostruisce l'aggregato semestrale.
     *
     * @return int Numero di pratiche importate
     */
    public function import(?OamSemester $semester = null, ?string $pratichePath = null, ?string $provvigioniPath = null): int
    {
        $semester ??= OamSemester::current();
        $pratiche = $this->readTsv($pratichePath ?? self::defaultPratichePath());
        $righeProvvigioni = $this->readTsv($provvigioniPath ?? self::defaultProvvigioniPath());
        $provvigioni = $this->aggregateProvvigioni($righeProvvigioni, $semester);

        $lock = Cache::lock('oam:import-pratiche', 900);

        if (! $lock->get()) {
            throw new \RuntimeException('Un import OAM e\' gia\' in corso. Riprovare tra qualche minuto.');
        }

        try {
            return $this->runImport($semester, $pratiche, $provvigioni);
        } finally {
            $lock->release();
        }
    }

    /**
     * @param  list<array<string, string>>  $pratiche
     * @param  array<string, array<string, float>>  $provvigioni
     */
    private function runImport(OamSemester $semester, array $pratiche, array $provvigioni): int
    {
        $companyId = app(CompanyResolver::class)->resolveId();
        $period = $semester->period();

        return DB::transaction(function () use ($semester, $pratiche, $provvigioni, $companyId, $period): int {
            OamPratiche::query()
                ->where('company_id', $companyId)
                ->where('period', $period)
                ->forceDelete();

            $imported = 0;

            foreach ($pratiche as $riga) {
                if (! $this->inSemestre($riga, $semester)) {
                    continue;
                }

                $cliente = $this->importSingle($riga, $provvigioni[$riga['ID Pratica']] ?? [], $companyId, $period);
                $this->abilitaProdotti($cliente, [$riga['Tipo Prodotto']]);
                $imported++;
            }

            $this->praticheService->applyBusinessRules($semester, $period, $companyId, segnalazione: $semester->semesterNumber === 2);

            app(OamSemestraleService::class)->aggregate(period: $period, companyId: $companyId);

            return $imported;
        });
    }

    /**
     * Stesse esclusioni di Pratica::perSemestreOam: niente preventivi, niente
     * pratiche respinte/rinunciate prima della fine del semestre.
     * Erogazione e inserimento rispetto al semestre li valuta il riepilogo
     * (OamSemestraleService).
     *
     * @param  array<string, string>  $riga
     */
    private function inSemestre(array $riga, OamSemester $semester): bool
    {
        $stato = $riga['Stato Pratica'];

        if (in_array($stato, self::STATI_NON_INSERITI, true)) {
            return false;
        }

        $annullata = $this->parseDate($riga['Data Annullamento']);

        if ($annullata !== null) {
            return $annullata->toDateString() >= $semester->end->toDateString();
        }

        return ! in_array($stato, self::STATI_CHIUSI, true);
    }

    /**
     * @param  array<string, string>  $riga
     * @param  array<string, float>  $provv
     */
    private function importSingle(array $riga, array $provv, string $companyId, string $period): Clienti
    {
        $istitutoNome = $riga['Denominazione Istituto'];
        $cliente = $this->istituto($istitutoNome, $companyId);
        $istitutoCanonico = Clienti::getClienteNomeByName($istitutoNome);

        $tipoProdotto = $riga['Tipo Prodotto'];
        $erogatedAt = $this->parseDate($riga['Data_erogazione']);
        $storno = (float) ($provv['storno'] ?? 0.0);

        OamPratiche::updateOrCreate(
            ['pratica' => $riga['ID Pratica'], 'company_id' => $companyId, 'period' => $period],
            [
                'company_id' => $companyId,
                'period' => $period,
                'istituto' => $istitutoCanonico,
                'intermediari_non_convenzionati' => blank($istitutoCanonico) ? 1 : 0,
                'agente' => null,
                'cliente' => null,
                'tipo_prodotto' => $tipoProdotto,
                'principal_type' => $cliente?->principal_type,
                'erogato_lordo' => $cliente?->principal_type === 'banca' ? $this->parseAmount($riga['Importo_Erogazione']) : 0.0,
                'sended_at' => $this->parseDate($riga['Data Inserimento Pratica']),
                'approved_at' => $erogatedAt,
                'erogated_at' => $erogatedAt,
                'rejected_at' => null,
                'provv_clientela' => 0.0,
                'provv_istituto_comp' => (float) ($provv['istituto'] ?? 0.0),
                'premi_istituto_comp' => 0.0,
                'payout_rete_credito' => (float) ($provv['agente'] ?? 0.0),
                'importo_retrocesse' => $storno,
                'num_rivalse' => $storno > 0 ? 1 : 0,
                'abi_name' => $cliente->abi_name ?: $istitutoNome,
            ]
        );

        return $cliente;
    }

    /**
     * La coppia istituto / prodotto trovata nei file abilita il codice OAM (is_active)
     * e lo associa all'istituto nella pivot clienti_oam.
     *
     * @param  list<string>  $tipiProdotto
     */
    private function abilitaProdotti(Clienti $cliente, array $tipiProdotto): void
    {
        foreach (array_unique(array_filter($tipiProdotto)) as $tipo) {
            // Stessa connessione della transazione (non quella `core` di OamCode): la pivot ha una chiave esterna su oam_codes e le due si bloccherebbero.
            DB::table('oam_codes')->where('tipo_prodotto', $tipo)->update(['is_active' => true, 'updated_at' => now()]);

            foreach (DB::table('oam_codes')->where('tipo_prodotto', $tipo)->pluck('id') as $codeId) {
                $existing = DB::table('clienti_oam')->where('clienti_id', $cliente->id)->where('oam_code_id', $codeId)->exists();

                if (! $existing) {
                    DB::table('clienti_oam')->insert([
                        'clienti_id' => $cliente->id,
                        'oam_code_id' => $codeId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Gli istituti di Sentovia sono banche: se non sono ancora in anagrafica
     * (clientis) vengono creati con principal_type banca.
     */
    private function istituto(string $nome, string $companyId): Clienti
    {
        $cliente = Clienti::query()->where('name', $nome)->first();

        if ($cliente !== null) {
            return $cliente;
        }

        return Clienti::create([
            'name' => $nome,
            'nome' => $nome,
            'abi_name' => $nome,
            'principal_type' => 'banca',
            'company_id' => $companyId,
            'is_active' => true,
            'is_dummy' => false,
        ]);
    }

    /**
     * Somma per pratica: compensi istituto, payout agente e storni (importi
     * negativi dell'istituto con data status nel semestre).
     *
     * @param  list<array<string, string>>  $righe
     * @return array<string, array<string, float>>
     */
    private function aggregateProvvigioni(array $righe, OamSemester $semester): array
    {
        $out = [];

        foreach ($righe as $riga) {
            $importo = $this->parseAmount($riga['Importo']);
            $chiave = match ($riga['Tipo']) {
                'Istituto' => 'istituto',
                'Agente' => 'agente',
                default => null,
            };

            if ($chiave === null) {
                continue;
            }

            $data = $this->parseDate($riga['Data Status']);

            if ($chiave === 'istituto' && $importo < 0 && $data !== null && $data->between($semester->start, $semester->end)) {
                $out[$riga['ID Pratica']]['storno'] = ($out[$riga['ID Pratica']]['storno'] ?? 0.0) + abs($importo);
            }

            $out[$riga['ID Pratica']][$chiave] = ($out[$riga['ID Pratica']][$chiave] ?? 0.0) + $importo;
        }

        return $out;
    }

    /**
     * @return list<array<string, string>>
     */
    private function readTsv(string $path): array
    {
        if (! is_file($path)) {
            throw new \RuntimeException("File Sentovia non trovato: {$path}");
        }

        $righe = preg_split('/\r\n|\n|\r/', trim((string) file_get_contents($path)));
        $header = array_map('trim', explode("\t", ltrim((string) array_shift($righe), "\xEF\xBB\xBF")));

        $out = [];

        foreach ($righe as $riga) {
            if (trim($riga) === '') {
                continue;
            }

            $celle = array_map('trim', explode("\t", $riga));
            $out[] = array_combine($header, array_pad($celle, count($header), ''));
        }

        return $out;
    }

    private function parseDate(string $value): ?CarbonImmutable
    {
        return $value === '' ? null : CarbonImmutable::createFromFormat('d/m/Y', $value)->startOfDay();
    }

    private function parseAmount(string $value): float
    {
        return (float) str_replace(',', '.', str_replace('.', '', $value));
    }
}
