<?php

namespace App\Models;

use App\Models\PROFORMA\Clienti;
use App\Models\PROFORMA\Provvigione;
use App\ValueObjects\OamSemester;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Unico\Core\Models\Pratica as CorePratica;
use Unico\Core\Models\PraticaStato as CorePraticaStato;
use Unico\Core\Models\TipoProdotto as CoreTipoProdotto;

/**
 * Pratica di finanziamento. Tabella `pratiches` su unico-core.
 */
class Pratica extends CorePratica
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'nrate' => 'integer',
        ]);
    }

    protected function isGuardableColumn($key): bool
    {
        return in_array($key, ['stato_pratica', 'tipo_prodotto'], true) ? true : parent::isGuardableColumn($key);
    }

    public function getAttribute($key)
    {
        return match ($key) {
            'stato_pratica' => $this->stato?->codice,
            'tipo_prodotto' => $this->tipoprodotto?->tipo_prodotto,
            default => parent::getAttribute($key),
        };
    }

    public function setAttribute($key, $value)
    {
        if ($key === 'stato_pratica') {
            $this->attributes['pratica_stato_id'] = $this->resolvePraticaStatoId($value);
            $this->unsetRelation('stato');
            $this->unsetRelation('praticaStato');

            return $this;
        }

        if ($key === 'tipo_prodotto') {
            $this->attributes['tipoprodotto_id'] = blank($value)
                ? null
                : CoreTipoProdotto::firstOrCreate(['tipo_prodotto' => $value], ['name' => $value])->getKey();
            $this->unsetRelation('tipoprodotto');

            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (self $pratica): void {
            if (blank($pratica->upload_at)) {
                $pratica->upload_at = now();
            }
        });
    }

    public function getNameAttribute(): string
    {
        return trim($this->codice_pratica.' - '.$this->cognome_cliente.' '.$this->nome_cliente);
    }

    public function agente()
    {
        return $this->belongsTo(Fornitore::class, 'partita_iva_agente', 'piva');
    }

    public function istituto()
    {
        return $this->belongsTo(Clienti::class, 'denominazione_banca', 'name');
    }

    public function oamCode()
    {
        return $this->belongsTo(OamCode::class, 'tipo_prodotto', 'tipo_prodotto');
    }

    public function stato(): BelongsTo
    {
        return $this->belongsTo(CorePraticaStato::class, 'pratica_stato_id');
    }

    public function annullato(): bool
    {
        return (bool) ($this->stato?->is_rejected ?? false);
    }

    public function provvigioni()
    {
        return $this->hasMany(Provvigione::class, 'id_pratica', 'id');
    }

    public function rejectedAtNelSemestre(OamSemester $semester): ?CarbonInterface
    {
        if ($this->rejected_at === null) {
            return null;
        }

        $rejectedAt = $this->rejected_at->year > $semester->end->year
            ? $this->rejected_at->copy()->subYears($this->rejected_at->year - $semester->end->year)
            : $this->rejected_at;

        return $rejectedAt->toDateString() < $semester->end->toDateString() ? $rejectedAt : null;
    }

    public function scopePerSemestreOam(Builder $query, ?OamSemester $semester = null): Builder
    {
        $semester ??= OamSemester::current();

        return $query
            ->where(fn (Builder $q) => $q->whereNull('rejected_at')->orWhereRaw(
                'IF(YEAR(rejected_at) > ?, DATE_SUB(rejected_at, INTERVAL (YEAR(rejected_at) - ?) YEAR), rejected_at) >= ?',
                [$semester->end->year, $semester->end->year, $semester->end->toDateString()]
            ))
            ->where('data_inserimento_pratica', '>=', '2025-01-01')
            ->whereHas('praticaStato', fn (Builder $q) => $q
                ->where('codice', '<>', 'INSERITA')
                ->whereNotIn('codice', ['DECLINATA', 'RINUNCIA CLIENTE', 'PRATICA RESPINTA']))
            ->where('is_notowned', 0)
            ->whereRaw('COALESCE(sended_at, approved_at, data_inserimento_pratica) <= ?', [$semester->end])
            ->whereDoesntHave('tipoprodotto', fn (Builder $q) => $q->whereIn('tipo_prodotto', ['Utenza', 'Polizza']))
            ->where(function (Builder $q) use ($semester) {
                $q->whereNull('erogated_at')
                    ->orWhere('erogated_at', '>=', $semester->start);
            });
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(PraticaStatusHistory::class, 'pratica_id')->orderBy('changed_at', 'desc');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function requisitiOperativi(): HasMany
    {
        return $this->hasMany(PraticaRequisitoOperativo::class, 'requestable_id')
            ->withAttributes(['requestable_type' => 'pratica']);
    }

    public function requisiti(): BelongsToMany
    {
        return $this->belongsToMany(
            PraticaRequisito::class,
            'document_requests',
            'requestable_id',
            'document_type_id'
        )
            ->wherePivot('requestable_type', 'pratica')
            ->withPivot(['id', 'status', 'requested_at', 'completed_at', 'notes'])
            ->withTimestamps();
    }

    public function generaRequisitiDaProdotto(): void
    {
        if (! $this->tipoprodotto_sub_id) {
            return;
        }

        $regole = RequisitoTipoFinanziamento::where('tipoprodotto_sub_id', $this->tipoprodotto_sub_id)
            ->orderBy('sort_order')
            ->get();

        foreach ($regole as $regola) {
            $this->requisitiOperativi()->firstOrCreate(
                ['document_type_id' => $regola->document_type_id],
                [
                    'status' => 'da_richiedere',
                    'requested_at' => null,
                    'is_required' => $regola->is_required,
                ]
            );
        }
    }

    public function haRequisitiObbligatoriIncompleti(): bool
    {
        return $this->requisitiOperativi()
            ->where('is_required', true)
            ->where('status', '!=', 'approvato')
            ->exists();
    }

    public function getRequisitiObbligatoriMancanti(): Collection
    {
        return $this->requisitiOperativi()
            ->with('requisito')
            ->where('is_required', true)
            ->where('status', '!=', 'approvato')
            ->get();
    }

    public function getPercentualeCompletamentoRequisitiAttribute(): int
    {
        $totale = $this->requisitiOperativi()->count();

        if ($totale === 0) {
            return 100;
        }

        $completati = $this->requisitiOperativi()
            ->where('status', 'approvato')
            ->count();

        return (int) round(($completati / $totale) * 100);
    }

    public function puoAvanzareAStato(PraticaStato $nuovoStato): bool
    {
        if (in_array($nuovoStato->codice, ['fascicolo_completo', 'deliberata', 'approvata'])) {
            return ! $this->haRequisitiObbligatoriIncompleti();
        }

        return true;
    }

    private function resolvePraticaStatoId(mixed $value): ?int
    {
        if (blank($value)) {
            return null;
        }

        $code = trim((string) $value);
        $stato = CorePraticaStato::query()->whereRaw('LOWER(codice) = ?', [mb_strtolower($code)])->first();

        if ($stato === null) {
            $stato = CorePraticaStato::query()->create([
                'codice' => $code,
                'name' => $code,
                'is_rejected' => in_array(mb_strtoupper($code), ['DECLINATA', 'PERIZIA KO', 'PRATICA RESPINTA', 'RINUNCIA CLIENTE'], true),
            ]);
        }

        return (int) $stato->getKey();
    }
}
