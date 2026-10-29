<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Unico\Core\Enums\DocumentStatus;
use Unico\Core\Models\DocumentSchedule as CoreDocumentSchedule;

class DocumentSchedule extends CoreDocumentSchedule
{
    protected $casts = [
        'expires_at' => 'date',
        'last_sent_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Relazione: Tipo di documento
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);  // Presume l'esistenza del model DocumentType
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo('documentable', 'documentable_type', 'documentable_id');
    }

    protected function entity(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->documentable_type || ! $this->documentable_id) {
                    return null;
                }

                // Recuperiamo la classe del Model dal morphMap
                $modelClass = Relation::getMorphedModel($this->documentable_type);

                return $modelClass ? $modelClass::find($this->documentable_id) : null;
            }
        );
    }

    /**
     * Chi deve gestire il documento: app della suite, voce di menu e funzione aziendale.
     *
     * @return array{app: string, menu: string, funzione: string}
     */
    public function gestione(): array
    {
        $isAnomaly = $this->status === DocumentStatus::ANOMALY->value;

        return match (true) {
            $isAnomaly && in_array($this->documentable_type, ['fornitore', 'cliente', 'clienti'], true) => ['app' => 'UnicoCoge', 'menu' => 'Provvigioni', 'funzione' => 'Contabile'],
            $this->documentable_type === 'employee' => ['app' => 'UnicoOam', 'menu' => 'Organigramma', 'funzione' => 'Segreteria'],
            $this->documentable_type === 'fornitore' => ['app' => 'UnicoOam', 'menu' => 'Produttori', 'funzione' => 'Segreteria rete'],
            $this->documentable_type === 'pratica' => ['app' => 'UnicoFinance', 'menu' => 'Pratiche', 'funzione' => 'Istruttore'],
            $this->documentable_type === 'client' && $this->documentable && ! $this->documentable->is_company => ['app' => 'UnicoFinance', 'menu' => 'Clienti', 'funzione' => 'Istruttore'],
            $this->documentable_type === 'client' => ['app' => 'UnicoOam', 'menu' => 'Consulenti', 'funzione' => 'Segreteria'],
            default => ['app' => 'UnicoOam', 'menu' => ucfirst((string) $this->documentable_type), 'funzione' => 'Segreteria'],
        };
    }

    /** Funzioni aziendali che gestiscono i documenti, con i ruoli dell'organigramma che le ricoprono. */
    public const FUNZIONI_RUOLI = [
        'Segreteria' => ['segreteria amministrativa'],
        'Segreteria rete' => ['segreteria di rete'],
        'Contabile' => ['contabile', 'contabile responsabile'],
        'Istruttore' => ['istruttore'],
    ];

    /**
     * Restringe alle scadenze gestite dalle funzioni indicate (stessa regola di gestione()).
     *
     * @param  Builder<static>  $query
     * @param  array<int, string>  $funzioni
     * @return Builder<static>
     */
    public function scopeGestiteDa(Builder $query, array $funzioni): Builder
    {
        $istruttore = fn (Builder $q): Builder => $q->where('documentable_type', 'pratica')
            ->orWhere(fn (Builder $c) => $c->where('documentable_type', 'client')
                ->whereIn('documentable_id', Client::query()->where('is_company', false)->select('id')));
        $contabile = fn (Builder $q): Builder => $q->where('status', DocumentStatus::ANOMALY->value)
            ->whereIn('documentable_type', ['fornitore', 'cliente', 'clienti']);
        $rete = fn (Builder $q): Builder => $q->where('documentable_type', 'fornitore')
            ->where('status', '!=', DocumentStatus::ANOMALY->value);

        return $query->where(function (Builder $q) use ($funzioni, $istruttore, $contabile, $rete): void {
            foreach ($funzioni as $funzione) {
                match ($funzione) {
                    'Istruttore' => $q->orWhere($istruttore),
                    'Contabile' => $q->orWhere($contabile),
                    'Segreteria rete' => $q->orWhere($rete),
                    'Segreteria' => $q->orWhere(fn (Builder $s) => $s->whereNot($istruttore)->whereNot($contabile)->whereNot($rete)),
                    default => null,
                };
            }
        });
    }

    /**
     * Funzioni ricoperte da un utente in base ai ruoli del suo dipendente (nessuna per admin e super_admin,
     * così il filtro non si applica di default).
     *
     * @return array<int, string>
     */
    public static function funzioniDelloUtente(?User $user): array
    {
        $ruolo = $user?->role instanceof \BackedEnum ? $user->role->value : $user?->role;
        if (in_array($ruolo, [UserRole::ADMIN->value, UserRole::SUPER_ADMIN->value], true)) {
            return [];
        }

        $ruoli = (array) $user?->employee?->employee_roles;

        return array_keys(array_filter(
            self::FUNZIONI_RUOLI,
            fn (array $ruoliFunzione): bool => array_intersect($ruoliFunzione, $ruoli) !== [],
        ));
    }
}
