<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ProducerTrainingSummaryMail;
use App\Models\Document;
use App\Models\PROFORMA\Fornitore;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class ProducerTrainingSummaryApiController extends Controller
{
    /**
     * Invia via email l'elenco dei produttori attivi (in ordine alfabetico)
     * con le relative ore di formazione totalizzate dai documenti collegati
     * (documents.documentable_type = 'fornitore' e training_hours > 0).
     */
    public function store(): JsonResponse
    {
        $fornitori = Fornitore::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $trainingHoursByFornitoreId = Document::query()
            ->where('documentable_type', 'fornitore')
            ->where('training_hours', '>', 0)
            ->selectRaw('documentable_id, SUM(training_hours) as total_hours')
            ->groupBy('documentable_id')
            ->pluck('total_hours', 'documentable_id');

        $rows = $fornitori->map(fn (Fornitore $fornitore): array => [
            'name' => $fornitore->name,
            'email' => $fornitore->email,
            'training_hours' => (int) ($trainingHoursByFornitoreId[$fornitore->id] ?? 0),
        ]);

        Mail::to('hassistosrl@gmail.com')->send(
            new ProducerTrainingSummaryMail(static::buildEmailBody($rows))
        );

        return response()->json(['status' => 'ok']);
    }

    /**
     * @param  Collection<int, array{name: ?string, email: ?string, training_hours: int}>  $rows
     */
    public static function buildEmailBody(Collection $rows): string
    {
        $tableRows = $rows->map(fn (array $row): string => sprintf(
            '<tr><td>%s</td><td>%s</td><td>%d</td></tr>',
            e($row['name'] ?? '-'),
            e($row['email'] ?? '-'),
            $row['training_hours']
        ))->implode('');

        return '<p>Elenco produttori attivi con le ore di formazione totalizzate:</p>'
            .'<table border="1" cellpadding="4" cellspacing="0">'
            .'<thead><tr><th>Produttore</th><th>Email</th><th>Ore formazione</th></tr></thead>'
            .'<tbody>'.$tableRows.'</tbody>'
            .'</table>';
    }
}
