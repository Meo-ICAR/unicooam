<?php

namespace App\Filament\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasPlanAccess
{
    /**
     * Controlla la visibilità del menu di navigazione (per Resource e Page)
     */
    public static function shouldRegisterNavigation(): bool
    {
        // Se la risorsa appartiene al gruppo Settings e checkPiano('settings') è false, nascondila
        if (static::$navigationGroup === 'Settings' && ! \checkPiano('settings')) {
            return false;
        }

        return \checkPiano(static::getFeatureKey(), static::class);
    }

    /**
     * Controlla la visibilità delle schede/relazioni (per RelationManager)
     */
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        // 🔹 Il "\" dice a PHP di cercare la funzione a livello GLOBALE
        return \checkPiano(static::getFeatureKey(), static::class);
    }

    /**
     * Helper interno per ricavare la chiave della feature in modo intelligente
     */
    protected static function getFeatureKey(): string
    {
        // 1. Priorità massima: $featureKey personalizzato nella classe
        if (property_exists(static::class, 'featureKey') && static::$featureKey !== null) {
            return static::$featureKey;
        }

        // 2. Se è un RelationManager, tenta di leggere la relazione
        if (method_exists(static::class, 'getRelationshipName')) {
            return static::getRelationshipName();
        }
        if (property_exists(static::class, 'relationship') && static::$relationship !== null) {
            return static::$relationship;
        }

        // 3. Se è una Resource/Page, tenta di leggere lo slug di Filament
        if (method_exists(static::class, 'getSlug')) {
            return static::getSlug();
        }

        // 4. Fallback generale: nome della classe convertito in kebab-case
        return Str::kebab(class_basename(static::class));
    }
}
