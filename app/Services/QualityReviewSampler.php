<?php

namespace App\Services;

use App\Enums\AuditStatus;
use App\Models\Audit;
use App\Models\PROFORMA\Pratica;
use App\Models\QualityReview;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Estrae un campione casuale di pratiche e crea, per ciascuna, un Audit di
 * tipo "quality" collegato alla sessione di controllo qualità.
 */
class QualityReviewSampler
{
    /**
     * Pratiche candidate al campionamento.
     *
     * Chiavi di $filters (tutte opzionali): period_from, period_to,
     * stati (list), tipi_prodotto (list), banche (list), agenti (list di p.iva),
     * erogato_min, erogato_max, escludi_gia_campionate (bool, default true).
     *
     * @param  array<string, mixed>  $filters
     * @return Builder<Pratica>
     */
    public function candidates(array $filters): Builder
    {
        $query = Pratica::query()->whereNull('rejected_at');

        $query
            ->when($filters['period_from'] ?? null, fn (Builder $q, $v) => $q->whereDate('data_inserimento_pratica', '>=', $v))
            ->when($filters['period_to'] ?? null, fn (Builder $q, $v) => $q->whereDate('data_inserimento_pratica', '<=', $v))
            ->when($filters['stati'] ?? null, fn (Builder $q, $v) => $q->whereIn('stato_pratica', $v))
            ->when($filters['tipi_prodotto'] ?? null, fn (Builder $q, $v) => $q->whereIn('tipo_prodotto', $v))
            ->when($filters['banche'] ?? null, fn (Builder $q, $v) => $q->whereIn('denominazione_banca', $v))
            ->when($filters['agenti'] ?? null, fn (Builder $q, $v) => $q->whereIn('partita_iva_agente', $v))
            ->when($filters['erogato_min'] ?? null, fn (Builder $q, $v) => $q->where('erogato', '>=', $v))
            ->when($filters['erogato_max'] ?? null, fn (Builder $q, $v) => $q->where('erogato', '<=', $v));

        if ($filters['escludi_gia_campionate'] ?? true) {
            // Cross-database: gli id delle pratiche già campionate stanno nel DB applicativo.
            $query->whereNotIn('id', Audit::query()
                ->where('origin_type', 'quality')
                ->where('auditable_type', 'pratica')
                ->select('auditable_id'));
        }

        return $query;
    }

    public function countCandidates(array $filters): int
    {
        return $this->candidates($filters)->count();
    }

    /**
     * Crea la sessione e un Audit per ogni pratica estratta a caso.
     *
     * @param  array<string, mixed>  $filters
     */
    public function createReview(string $name, int $sampleSize, array $filters, ?int $reviewerUserId = null, ?string $companyId = null): QualityReview
    {
        $pratiche = $this->candidates($filters)->inRandomOrder()->limit($sampleSize)->get();

        return DB::connection('mysql')->transaction(function () use ($name, $sampleSize, $filters, $reviewerUserId, $companyId, $pratiche): QualityReview {
            $review = QualityReview::create([
                'company_id' => $companyId,
                'name' => $name,
                'reviewer_user_id' => $reviewerUserId,
                'period_from' => $filters['period_from'] ?? null,
                'period_to' => $filters['period_to'] ?? null,
                'sample_size' => $pratiche->count(),
                'filters' => $filters + ['richiesti' => $sampleSize],
            ]);

            foreach ($pratiche as $pratica) {
                $review->audits()->create([
                    'company_id' => $review->company_id,
                    'name' => 'Controllo qualità pratica '.$pratica->codice_pratica,
                    'auditable_type' => 'pratica',
                    'auditable_id' => $pratica->id,
                    'auditor_name' => $review->reviewer?->name,
                    'scheduled_at' => now(),
                    'status' => AuditStatus::PLANNED,
                    'origin_type' => 'quality',
                    'execution_method' => 'documentale',
                    'scope' => 'Controllo qualità sulla pratica '.$pratica->codice_pratica,
                ]);
            }

            return $review;
        });
    }
}
