<?php

use App\Enums\PlanType;

if (! function_exists('checkPiano')) {
    /**
     * Verifica se una funzionalità è attiva per il piano corrente.
     * Se APP_PLAN non è configurato nel .env, di default usa FULL.
     */
    function checkPiano(string $feature, ?string $callerClass = null): bool
    {
        // 1. Prende la stringa dal .env (fallback su 'full' se la chiave manca)
        $planValue = env('APP_PLAN', PlanType::FULL->value);

        // 2. Converte la stringa nell'Enum (fallback su FULL se il valore non è valido)
        $plan = PlanType::tryFrom($planValue) ?? PlanType::FULL;

        return $plan->hasFeature($feature, $callerClass);
    }
}
