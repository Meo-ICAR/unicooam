<?php

namespace App\Http\Controllers\Api;

use App\Console\Commands\StatusCheckCommand;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * Espone a UnicoBPM lo stato dei controlli (comandi che estendono StatusCheckCommand):
 * solo valore e severity, senza inviare nulla. La whitelist è implicita: sono interrogabili
 * esclusivamente i comandi di questo tipo.
 */
class CheckStatusApiController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'checks' => collect($this->checkCommands())->map(fn (StatusCheckCommand $command) => $command->getDescription())->all(),
        ]);
    }

    public function show(string $command): JsonResponse
    {
        $check = $this->checkCommands()[$command] ?? null;

        if (! $check) {
            return response()->json(['message' => "Check '{$command}' non disponibile."], 404);
        }

        // Il comando non gira da riga di comando: gli si dà un input, altrimenti option() non ha da dove leggere. Via API non si ricalcola
        // lo scadenziario nella richiesta (con tutti i documenti supera il limite di memoria del web): lo fa documents:sync-schedule di notte.
        $options = $check->getDefinition()->hasOption('no-sync') ? ['--no-sync' => true] : [];
        $check->setInput(new ArrayInput($options, $check->getDefinition()));

        return response()->json(['command' => $command, ...$check->checkStatus()->toArray()]);
    }

    /**
     * @return array<string, StatusCheckCommand>
     */
    private function checkCommands(): array
    {
        return array_filter(Artisan::all(), fn ($command) => $command instanceof StatusCheckCommand);
    }
}
