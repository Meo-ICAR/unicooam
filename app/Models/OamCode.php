<?php

namespace App\Models;

use App\Models\PROFORMA\Clienti; // Assicurati di importarlo
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Builder as QueryBuilder;

class OamCode extends Model
{
    use HasFactory;

    // Definito esplicitamente per mappare la tabella plurale corretta
    protected $connection = 'mysql';

    protected $table = 'oam_codes';

    protected $fillable = [
        'code',
        'name',
        'description',
        'tipo_prodotto',
        'is_dummy',
        'is_active',
        // 'submission_type',
    ];

    public function clienti(): BelongsToMany
    {
        return $this
            ->belongsToMany(
                Clienti::class,
                'unicooam.clienti_oam',  // Qualificata: la relazione interroga la connessione di Clienti (mysql_proforma), dove la tabella pivot non esiste
                'oam_code_id',  // Invertito: prima la chiave di questo modello nella pivot
                'clienti_id'  // Poi la chiave del modello correlato
            )
            ->where('is_active', true)  // <-- FILTRO: Mostra solo Client attivi
            ->withPivot('dal', 'al')
        //    ->withPivot('dal', 'al', 'submission_type')
            ->withTimestamps();
    }

    /**
     * Vero se il finanziatore (Clienti.abi_name) risulta convenzionato per il
     * prodotto creditizio indicato (matchato su OamCode.description, lo
     * stesso testo usato in OamSemestrale.prodotto_creditizio).
     */
    public static function isIstitutoConvenzionato(string $abiName, string $prodottoCreditizio): bool
    {
        $oamCode = static::where('description', $prodottoCreditizio)->first();

        if (! $oamCode) {
            return false;
        }

        return $oamCode->clienti()->where('abi_name', $abiName)->exists();
    }

    /**
     * Numero di finanziatori convenzionati per il prodotto creditizio
     * indicato (matchato su OamCode.description), indipendentemente da
     * quali finanziatori compaiano poi in OamSemestrale.
     */
    public static function countConvenzioniPerProdotto(string $prodottoCreditizio): int
    {
        $oamCode = static::where('description', $prodottoCreditizio)->first();

        return $oamCode?->clienti()->count() ?? 0;
    }

    /**
     * Filtra i codici OAM che hanno almeno un finanziatore convenzionato
     * (attivo). Costruita come EXISTS con tabelle qualificate per schema
     * (invece di whereHas('clienti')) perché la subquery gira sulla
     * connessione di OamCode (mysql/unicooam), dove "clientis" non esiste
     * senza il prefisso del database di Clienti (mysql_proforma/proforma).
     */
    public function scopeHasConvenzioni(Builder $query): Builder
    {
        return $query->whereExists(fn (QueryBuilder $subQuery) => $this->addConvenzioniExistsConstraints($subQuery));
    }

    public function scopeDoesntHaveConvenzioni(Builder $query): Builder
    {
        return $query->whereNotExists(fn (QueryBuilder $subQuery) => $this->addConvenzioniExistsConstraints($subQuery));
    }

    protected function addConvenzioniExistsConstraints(QueryBuilder $subQuery): void
    {
        $subQuery
            ->selectRaw('1')
            ->from('unicooam.clienti_oam')
            ->join('proforma.clientis', 'proforma.clientis.id', '=', 'unicooam.clienti_oam.clienti_id')
            ->whereColumn('unicooam.clienti_oam.oam_code_id', $this->getTable().'.id')
            ->where('proforma.clientis.is_active', true)
            ->whereNull('proforma.clientis.deleted_at');
    }
}
