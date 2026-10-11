<?php

/*
 * Comandi che un sistema esterno (UnicoBPM) può lanciare via API (bridge di unico-core: POST /api/commands/{comando}).
 * Solo quelli elencati, e solo con le opzioni indicate. L'esecuzione è in coda e ha un esito leggibile (GET /api/commands/runs/{id}).
 */
return [
    'documents:sync-and-email' => [
        'description' => 'Aggiorna lo scadenziario documenti e invia via email l\'export Excel.',
        'options' => [],
    ],
    'documents:sync-schedule' => [
        'description' => 'Ricalcola lo scadenziario documenti.',
        'options' => [],
    ],
    'sharepoint:retry-uploads' => [
        'description' => 'Riprova i caricamenti su SharePoint rimasti in sospeso.',
        'options' => ['local'],
    ],
];
