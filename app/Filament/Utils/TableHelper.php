<?php

namespace App\Filament\Utils;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter; // Importa il filtro di Filament
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

            // Ricerca polimorfica universale e sicura su tutto il morphMap
            ->searchable(query: function ($query, string $search) use ($relationName) {
                $allMorphAliases = array_keys(Relation::morphMap());

                $query->whereHasMorph($relationName, $allMorphAliases, function ($q) use ($search) {
                    $modelInstance = $q->getModel();
                    $tableName = $modelInstance->getTable();

                    // Controllo preventivo delle colonne per evitare crash SQL
                    $q->where(function ($subQuery) use ($search, $tableName) {
                        if (Schema::hasColumn($tableName, 'full_name')) {
                            $subQuery->orWhere('full_name', 'like', "%{$search}%");
                        }

                        if (Schema::hasColumn($tableName, 'name')) {
                            $subQuery->orWhere('name', 'like', "%{$search}%");
                        }
                    });
                });
            });
    }
}
