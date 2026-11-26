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
    'audits:open-from-email' => [
        'description' => 'Apre un audit interno per ciascun produttore indicato, nato da un\'email di UnicoPEC (es. verifiche ispettive semestrali), e gli invia il testo standard con gli allegati.',
        'options' => ['email', 'produttori'],
    ],
    'sharepoint:retry-uploads' => [
        'description' => 'Riprova i caricamenti su SharePoint rimasti in sospeso.',
        'options' => ['local'],
    ],
];
