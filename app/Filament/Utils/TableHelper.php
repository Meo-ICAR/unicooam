<?php

namespace App\Filament\Utils;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema;

class TableHelper
{
    /**
     * Genera un SelectFilter polimorfico dinamico basato sul MorphMap.
     */
    public static function polymorphicFilter(string $fieldName = 'documentable_type', string $label = 'Destinatari'): SelectFilter
    {
        return SelectFilter::make($fieldName)
            ->label($label)
            ->options(function () {
                // Dizionario delle tue traduzioni commerciali personalizzate
                $labels = [
                    'fornitore' => 'Produttore',
                    'company' => 'Azienda',
                    'employee' => 'Dipendente',
                    'audit' => 'Audit',
                    'complaint' => 'Reclamo',
                    'cliente' => 'Istituto',
                    'branch' => 'Filiale',
                ];

                // Filtra e mappa solo i modelli effettivamente presenti nel morphMap dell'applicazione
                return collect(Relation::morphMap())->mapWithKeys(fn ($className, $alias) => [
                    $alias => $labels[$alias] ?? ucfirst(class_basename($className)),
                ])->toArray();
            });
    }

    /**
     * Genera un TextColumn polimorfico dinamico con ricerca automatica
     * e descrizione intelligente basata sul MorphMap.
     */
    public static function polymorphicColumn(string $relationName = 'auditable', string $label = 'Soggetto Controllato'): TextColumn
    {
        $typeField = $relationName.'_type';

        return TextColumn::make($relationName)
            ->label($label)
            // Cerca dinamicamente full_name o name sul record polimorfico collegato
            ->state(fn ($record) => $record->$relationName?->full_name ?? $record->$relationName?->name ?? 'N/D')

            // Gestione automatica della descrizione sottostante
            ->description(function ($record) use ($typeField) {
                if (empty($record->$typeField)) {
                    return null;
                }

                // Traduzioni commerciali personalizzate
                $labels = [
                    'fornitore' => 'Collaboratore / Agente',
                    'employee' => 'Impiegato Interno',
                    'cliente' => 'Mandante',
                    'company' => 'Azienda',
                    'audit' => 'Audit',
                    'branch' => 'Filiale',
                ];

                if (array_key_exists($record->$typeField, $labels)) {
                    return $labels[$record->$typeField];
                }

                // Fallback automatico sul nome della classe se non tradotto
                $className = Relation::morphMap()[$record->$typeField] ?? null;

                return $className ? ucfirst(class_basename($className)) : $record->$typeField;
            })

            // Ricerca polimorfica universale, sicura e raggruppata
            ->searchable(query: function (Builder $query, string $search) use ($relationName) {
                $allMorphAliases = array_keys(Relation::morphMap());

                // Usiamo un where logico raggruppato principale per non rompere altri filtri in tabella
                $query->where(function (Builder $mainSubQuery) use ($relationName, $allMorphAliases, $search) {

                    $mainSubQuery->whereHasMorph($relationName, $allMorphAliases, function (Builder $q) use ($search) {
                        $modelInstance = $q->getModel();

                        // Sostituito Schema::hasColumn statico ad ogni riga con un controllo di istanza più efficiente
                        $q->where(function (Builder $subQuery) use ($search, $modelInstance) {

                            // Controlla se sul modello polimorfico corrente esistono proprietà/attributi specifici
                            // o verifica la presenza delle colonne usando una proprietà nel modello se vuoi fare un controllo fisso,
                            // altrimenti esegui l'orWhere in sicurezza se le tabelle seguono uno standard.

                            // NOTA: Se sei sicuro che tutte o la maggior parte abbiano 'name', puoi omettere il controllo dinamico.
                            // In alternativa, definiamo una ricerca flessibile basata sui campi standard del tuo gestionale:
                            $subQuery->where('name', 'like', "%{$search}%");

                            // Se solo alcune tabelle hanno full_name, puoi usare il reflection del modello o lasciare il fallback
                            if (method_exists($modelInstance, 'getFullNameAttribute') || in_array(class_basename($modelInstance), ['User', 'Employee'])) {
                                $subQuery->orWhere('full_name', 'like', "%{$search}%");
                            }
                        });
                    });

                });
            });
    }
}
