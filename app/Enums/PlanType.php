<?php

namespace App\Enums;

use Illuminate\Support\Facades\Log;

enum PlanType: string
{
    case ESSENTIAL = 'essential';
    case BASE = 'base';
    case GOLD = 'gold';
    case FULL = 'full';
    case DEBUG = 'debug';

    /**
     * Ritorna l'elenco delle funzionalità abilitate per ciascun piano.
     */
    public function features(): array
    {
        // Feature del piano Essential
        $essentialFeatures = [
            'basic_reports',
        ];

        // Feature del piano Base
        $baseFeatures = [
            'Anagrafica',
            'fornitores',
            'Informativo',
            'Sedi',
            'Branches',
            'employees',
        ];

        $goldFeatures = [
            'audits',
            'complaint-registries',
            'document-schedules',
            'suspicious-activity-reports',

        ];

        // Mappatura e gerarchia dei piani
        return match ($this) {
            self::ESSENTIAL => $essentialFeatures,
            self::BASE => $baseFeatures,
            self::GOLD => [
                $baseFeatures, // <-- Eredita automaticamente tutte le feature di BASE
                $goldFeatures,
                'audit-resource',
                'documents-relation-manager',
            ],
            self::FULL,
            self::DEBUG => ['*'], // '*' indica che ha accesso a TUTTE le feature
        };
    }

    /**
     * Verifica se una specifica feature è inclusa nel piano attivo.
     */
    public function hasFeature(string $feature, ?string $callerClass = null): bool
    {
        $features = $this->features();

        // FULL e DEBUG sbloccano tutto (*), altrimenti verifica l'array del piano
        $hasAccess = in_array('*', $features) || in_array($feature, $features);

        // Se siamo nel piano DEBUG, registra nel log di Laravel chi sta facendo la richiesta
        if ($this === self::DEBUG) {
            $user = auth()->user();

            Log::debug('🔍 [PLAN DEBUG] Controllo visibilità feature', [
                'feature_richiesta' => $feature,
                'esito_accesso' => $hasAccess ? 'ABILITATO' : 'NEGATO',
                'chiamato_da' => $callerClass ?? $this->getCallerFromTrace(),
                'utente' => $user ? "{$user->email} (ID: {$user->id})" : 'Non autenticato',
                'ip' => request()->ip(),
                'url_corrente' => request()->fullUrl(),
            ]);
        }

        return $hasAccess;
    }

    /**
     * Recupera la classe chiamante dallo stack trace se non passata esplicitamente.
     */
    private function getCallerFromTrace(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);

        return $trace[3]['class'] ?? ($trace[2]['class'] ?? 'Sconosciuto');
    }
}
