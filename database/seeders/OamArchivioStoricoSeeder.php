<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\OamArchivio;
use App\Services\OamArchivioService;
use Illuminate\Database\Connection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Simula la storicizzazione del primo semestre 2026 (period 202606):
 * carica oam_semestrales e oam_pratiches dal database unicooam_old nelle
 * tabelle di lavoro e li archivia con OamArchivioService, svuotando le
 * tabelle di lavoro come farebbe l'azione "Storicizza".
 */
class OamArchivioStoricoSeeder extends Seeder
{
    private const PERIOD = '202606';

    private const NOTE = 'Storicizzazione 1° semestre 2026 (seed da unicooam_old)';

    private const OLD_DATABASE = 'unicooam_old';

    public function run(): void
    {
        // La FK company_id punta a companies della connessione di default: se la
        // company risolta (connessione core) lì non esiste, si archivia senza company.
        $companyId = Company::query()->value('id');
        $companyId = $companyId && DB::table('companies')->where('id', $companyId)->exists() ? (int) $companyId : null;

        if (OamArchivio::query()->where('period', self::PERIOD)->where('note', self::NOTE)->exists()) {
            $this->command?->warn('Archivio 1° semestre 2026 già presente: nulla da fare.');

            return;
        }

        $old = $this->oldConnection();

        DB::transaction(function () use ($old, $companyId): void {
            foreach (['oam_semestrales', 'oam_pratiches'] as $table) {
                $this->copiaDaOld($old, $table, $companyId);
            }

            $archivio = app(OamArchivioService::class)->archivia(
                period: self::PERIOD,
                companyId: $companyId,
                note: self::NOTE,
                svuota: true,
            );

            $this->command?->info("Creato archivio #{$archivio->id}: {$archivio->label()}");
        });
    }

    private function oldConnection(): Connection
    {
        config(['database.connections.unicooam_old' => [
            ...config('database.connections.'.config('database.default')),
            'database' => self::OLD_DATABASE,
        ]]);

        return DB::connection('unicooam_old');
    }

    /**
     * Copia le righe del semestre dal database vecchio nella tabella di
     * lavoro corrente, riallineando company_id alla company attuale.
     */
    private function copiaDaOld(Connection $old, string $table, ?int $companyId): void
    {
        $colonne = Schema::getColumnListing($table);

        DB::table($table)->where('period', self::PERIOD)->delete();

        $old->table($table)
            ->where('period', self::PERIOD)
            ->orderBy('id')
            ->chunk(500, function ($righe) use ($table, $colonne, $companyId): void {
                DB::table($table)->insert($righe->map(fn (object $riga): array => [
                    ...array_diff_key(array_intersect_key((array) $riga, array_flip($colonne)), ['id' => true]),
                    'company_id' => $companyId,
                ])->all());
            });
    }
}
