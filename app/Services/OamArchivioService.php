<?php

namespace App\Services;

use App\Models\OamArchivio;
use App\Models\OamArchivioRiga;
use App\Models\OamPratiche;
use App\Models\OamSemestrale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class OamArchivioService
{
    private const CHUNK = 500;

    /**
     * Storicizza le righe di oam_semestrales e oam_pratiches del semestre
     * indicato in un nuovo archivio, richiamabile con richiama().
     *
     * @param  bool  $svuota  Se true, rimuove dalle tabelle di lavoro le righe archiviate.
     */
    public function archivia(string $period, ?int $companyId = null, ?string $note = null, bool $svuota = false, ?int $userId = null): OamArchivio
    {
        return DB::transaction(function () use ($period, $companyId, $note, $svuota, $userId): OamArchivio {
            $archivio = OamArchivio::create([
                'company_id' => $companyId,
                'period' => $period,
                'note' => $note,
                'archived_by' => $userId,
            ]);

            $semestrali = $this->copiaRighe(OamSemestrale::query(), $period, $companyId, $archivio, OamArchivioRiga::TIPO_SEMESTRALE);
            $pratiche = $this->copiaRighe(OamPratiche::query(), $period, $companyId, $archivio, OamArchivioRiga::TIPO_PRATICA);

            $archivio->update(['semestrali_count' => $semestrali, 'pratiche_count' => $pratiche]);

            if ($svuota) {
                $this->svuotaPeriodo($period, $companyId);
            }

            return $archivio;
        });
    }

    /**
     * Richiama un archivio: sostituisce le righe correnti dello stesso
     * semestre/azienda con quelle archiviate.
     *
     * @return array{semestrali: int, pratiche: int}
     */
    public function richiama(OamArchivio $archivio): array
    {
        return DB::transaction(function () use ($archivio): array {
            $this->svuotaPeriodo($archivio->period, $archivio->company_id);

            $semestrali = $this->ripristinaRighe($archivio, OamArchivioRiga::TIPO_SEMESTRALE, new OamSemestrale);
            $pratiche = $this->ripristinaRighe($archivio, OamArchivioRiga::TIPO_PRATICA, new OamPratiche);

            $archivio->update(['restored_at' => now()]);

            return ['semestrali' => $semestrali, 'pratiche' => $pratiche];
        });
    }

    private function copiaRighe($query, string $period, ?int $companyId, OamArchivio $archivio, string $tipo): int
    {
        $totale = 0;

        $query
            ->where('period', $period)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->orderBy('id')
            ->chunkById(self::CHUNK, function ($righe) use ($archivio, $tipo, &$totale): void {
                OamArchivioRiga::query()->insert($righe->map(fn (Model $riga): array => [
                    'oam_archivio_id' => $archivio->id,
                    'tipo' => $tipo,
                    'dati' => json_encode(collect($riga->getRawOriginal())->except('id')->all()),
                ])->all());

                $totale += $righe->count();
            });

        return $totale;
    }

    private function ripristinaRighe(OamArchivio $archivio, string $tipo, Model $modello): int
    {
        $totale = 0;

        $archivio->righe()
            ->where('tipo', $tipo)
            ->orderBy('id')
            ->chunkById(self::CHUNK, function ($righe) use ($modello, &$totale): void {
                $modello->newQuery()->insert($righe->map(fn (OamArchivioRiga $riga): array => $riga->dati)->all());

                $totale += $righe->count();
            });

        return $totale;
    }

    private function svuotaPeriodo(string $period, ?int $companyId): void
    {
        $filtra = fn ($query) => $query
            ->where('period', $period)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId));

        $filtra(OamSemestrale::query())->delete();
        $filtra(OamPratiche::query()->withTrashed())->forceDelete();
    }
}
