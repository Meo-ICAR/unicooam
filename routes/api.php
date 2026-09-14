<?php

use App\Http\Controllers\Api\DocumentScheduleSyncApiController;
use App\Http\Controllers\Api\ModelFieldsApiController;
use App\Http\Controllers\Api\ModelFieldValueApiController;
use App\Http\Controllers\Api\ProducerTrainingSummaryApiController;
use App\Http\Controllers\Api\UserLookupApiController;
use Illuminate\Support\Facades\Route;

// Consumato da UnicoBPM per configurare i processi (select dei campi
// disponibili) e per scrivere un campo senza accedere direttamente ai
// modelli di questa app. Nessuna autenticazione per ora (ambiente non di
// produzione), da aggiungere prima del rilascio.
Route::get('/models/{model}/fields', [ModelFieldsApiController::class, 'show'])->name('api.models.fields');
Route::get('/models/{model}/{id}', [ModelFieldValueApiController::class, 'show'])->name('api.models.show-record');
Route::patch('/models/{model}/{id}', [ModelFieldValueApiController::class, 'update'])->name('api.models.update-field');

// Consumato da UnicoBPM per verificare se l'utente loggato ha un account anche qui.
Route::get('/users/lookup', [UserLookupApiController::class, 'show'])->name('api.users.lookup');

// Rilancia la sincronizzazione dello scadenziario documenti e invia via
// email il relativo export Excel. Nessuna autenticazione per ora (ambiente
// non di produzione), da aggiungere prima del rilascio.
Route::post('/document-schedules/sync-and-email', [DocumentScheduleSyncApiController::class, 'store'])->name('api.document-schedules.sync-and-email');

// Invia via email l'elenco dei produttori attivi con le ore di formazione
// totalizzate. Nessuna autenticazione per ora (ambiente non di produzione),
// da aggiungere prima del rilascio.
Route::post('/fornitori/training-summary-email', [ProducerTrainingSummaryApiController::class, 'store'])->name('api.fornitori.training-summary-email');
