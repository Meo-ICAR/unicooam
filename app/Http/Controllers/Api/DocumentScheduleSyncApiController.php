<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Unico\Core\Bridge\CommandDispatcher;

class DocumentScheduleSyncApiController extends Controller
{
    /**
     * Aggiorna lo scadenziario documenti (come l'azione "Aggiorna scadenziario") e invia via email il relativo export Excel
     * a hassistosrl@gmail.com, in cc a piergiuseppe.meo@gmail.com. Il lavoro è in coda (comando `documents:sync-and-email`,
     * lo stesso del bridge): la risposta è 202 con l'id del lancio, il cui esito si legge da GET /api/commands/runs/{id}.
     */
    public function store(Request $request, CommandDispatcher $dispatcher): JsonResponse
    {
        $run = $dispatcher->dispatch('documents:sync-and-email', [], $request->header('X-Correlation-Id'), 'api');

        return response()->json(['status' => 'queued', 'run_id' => $run->id], 202);
    }
}
