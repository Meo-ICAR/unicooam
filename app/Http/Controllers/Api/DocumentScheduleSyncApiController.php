<?php

namespace App\Http\Controllers\Api;

use App\Filament\Resources\DocumentSchedules\DocumentScheduleResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class DocumentScheduleSyncApiController extends Controller
{
    /**
     * Aggiorna lo scadenziario documenti (come l'azione "Aggiorna scadenziario")
     * e invia via email il relativo export Excel (lo stesso generato dal
     * bottone "Esporta Excel") a hassistosrl@gmail.com, in cc a
     * piergiuseppe.meo@gmail.com.
     */
    public function store(): JsonResponse
    {
        DocumentScheduleResource::syncAndEmailScheduleReport();

        return response()->json(['status' => 'ok']);
    }
}
