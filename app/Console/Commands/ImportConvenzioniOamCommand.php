<?php

namespace App\Console\Commands;

use App\Models\PROFORMA\Clienti;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportConvenzioniOamCommand extends Command
{
    protected $signature = 'oam:importa-convenzioni
        {--da=unicooam : Database di origine con oam_codes e clienti_oam}
        {--anagrafica-vecchia=proforma : Database con la vecchia anagrafica clientis (UUID), usata se legacy_id non basta}
        {--dry-run : Mostra cosa farebbe senza scrivere}';

    protected $description = 'Importa in unicosuite i codici OAM mancanti e le convenzioni per istituto (clienti_oam) da un database precedente';

    public function handle(): int
    {
        $da = $this->option('da');
        $vecchia = $this->option('anagrafica-vecchia');
        $dryRun = (bool) $this->option('dry-run');

        DB::beginTransaction();

        $codici = $this->importaCodici($da);
        $this->importaConvenzioni($da, $vecchia, $codici);

        $dryRun ? DB::rollBack() : DB::commit();

        $this->info($dryRun ? 'Simulazione conclusa: nessuna scrittura.' : 'Importazione conclusa.');

        return self::SUCCESS;
    }

    /**
     * Aggiunge i codici OAM che mancano (stesso code + tipo_prodotto) senza toccare quelli esistenti.
     *
     * @return array<int, int> id del codice nel database di origine => id in unicosuite
     */
    private function importaCodici(string $da): array
    {
        $mappa = [];
        $nuovi = 0;

        foreach (DB::table("{$da}.oam_codes")->orderBy('id')->get() as $origine) {
            $id = DB::table('oam_codes')
                ->where('code', $origine->code)
                ->where('tipo_prodotto', $origine->tipo_prodotto)
                ->value('id');

            if ($id === null) {
                $id = DB::table('oam_codes')->insertGetId([
                    'code' => $origine->code,
                    'name' => $origine->name,
                    'description' => $origine->description,
                    'tipo_prodotto' => $origine->tipo_prodotto,
                    'is_dummy' => $origine->is_dummy,
                    'is_active' => $origine->is_active,
                    'submission_type' => $origine->submission_type,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $nuovi++;
                $this->line("Codice aggiunto: {$origine->code} {$origine->tipo_prodotto}");
            }

            $mappa[(int) $origine->id] = (int) $id;
        }

        $this->info("Codici OAM: {$nuovi} aggiunti.");

        return $mappa;
    }

    /**
     * @param  array<int, int>  $codici
     */
    private function importaConvenzioni(string $da, string $vecchia, array $codici): void
    {
        $inseriti = 0;
        $esistenti = 0;
        $nonRisolti = [];

        foreach (DB::table("{$da}.clienti_oam")->orderBy('id')->get() as $riga) {
            $codeId = $codici[(int) $riga->oam_code_id] ?? null;
            $cliente = $this->risolviCliente((string) $riga->clienti_id, $vecchia);

            if ($codeId === null || $cliente === null) {
                $nonRisolti[] = [$riga->clienti_id, $riga->oam_code_id];

                continue;
            }

            $chiave = ['clienti_id' => $cliente->id, 'oam_code_id' => $codeId];

            if (DB::table('clienti_oam')->where($chiave)->exists()) {
                $esistenti++;

                continue;
            }

            DB::table('clienti_oam')->insert($chiave + [
                'dal' => $riga->dal,
                'al' => $riga->al,
                'created_at' => $riga->created_at ?? now(),
                'updated_at' => now(),
            ]);
            $inseriti++;
        }

        $this->info("Convenzioni: {$inseriti} inserite, {$esistenti} già presenti, ".count($nonRisolti).' non risolte.');

        foreach ($nonRisolti as [$uuid, $codeId]) {
            $this->warn("Non risolta: cliente {$uuid}, codice {$codeId}");
        }
    }

    /**
     * Cliente (istituto non dummy) corrispondente al vecchio UUID: prima per legacy_id, poi tramite
     * nome e partita IVA nella vecchia anagrafica. Con piu' istituti non dummy sulla stessa partita IVA
     * si sceglie quello con lo stesso nome; per un dummy senza un unico non dummy resta l'istituto originale.
     */
    private function risolviCliente(string $uuid, string $vecchia): ?Clienti
    {
        $cliente = Clienti::query()->where('legacy_id', $uuid)->first();

        if ($cliente === null) {
            $vecchio = DB::table("{$vecchia}.clientis")->where('id', $uuid)->first(['name', 'piva']);

            if ($vecchio === null) {
                return null;
            }

            $stessaIva = filled($vecchio->piva) ? Clienti::query()->where('piva', $vecchio->piva)->get() : collect();
            $cliente = $stessaIva->firstWhere('name', $vecchio->name)
                ?? Clienti::query()->where('name', $vecchio->name)->first()
                ?? ($stessaIva->where('is_dummy', false)->count() === 1 ? $stessaIva->firstWhere('is_dummy', false) : null);
        }

        if ($cliente === null || ! $cliente->is_dummy) {
            return $cliente;
        }

        // Dummy: si passa all'istituto non dummy solo se e' uno solo sulla stessa partita IVA, altrimenti resta l'originale.
        $nonDummy = Clienti::query()->where('piva', $cliente->piva)->where('is_dummy', false)->get();

        return filled($cliente->piva) && $nonDummy->count() === 1 ? $nonDummy->first() : $cliente;
    }
}
