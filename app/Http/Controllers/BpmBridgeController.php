<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class BpmBridgeController extends Controller
{
    public function handle(Request $request, $subject_id)
    {
        $token = $request->query('token');
        $userEmail = $request->query('user_email');

        if (! $token || ! $userEmail) {
            abort(403, 'Parametri di sicurezza mancanti.');
        }

        // Recuperiamo l'URL di base del BPM dalla configurazione
        $bpmBaseUrl = config('services.bpm.url');

        // 1. VERIFICA DEL TOKEN (Sicurezza)
        // La chiamata HTTP è ora completamente dinamica
        $response = Http::post("{$bpmBaseUrl}/api/verify-token", [
            'token' => $token,
            'email' => $userEmail,
        ]);

        if ($response->failed() || ! $response->json('valid')) {
            abort(403, 'Token BPM non valido o scaduto.');
        }

        // 2. AUTENTICAZIONE DELL'UTENTE
        // Cerchiamo l'utente nell'applicazione esterna usando l'email passata dal BPM
        $user = User::where('email', $userEmail)->first();

        if (! $user) {
            abort(404, 'Utente non censito in questa applicazione.');
        }

        // Eseguiamo il login automatico della sessione per questo utente
        Auth::login($user);

        // 3. REINDIRIZZAMENTO ALLA PAGINA CORRETTA
        // Ora che l'utente è loggato, lo mandiamo direttamente sulla schermata del soggetto
        return redirect()->route('agents.show', ['agent' => $subject_id])
            ->with('message', 'Accesso effettuato tramite BPM');
    }
}
