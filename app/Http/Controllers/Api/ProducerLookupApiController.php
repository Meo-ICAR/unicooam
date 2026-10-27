<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Fornitore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Unico\Core\Models\Company;

class ProducerLookupApiController extends Controller
{
    /**
     * Consumato da unicoagent per sapere se un numero WhatsApp è di un produttore (fornitore) e di quale società: unicoagent non legge
     * il database dei fornitori, chiede qui. Il numero si confronta sulle ultime 10 cifre, in qualunque formato sia scritto il telefono.
     * Risponde sempre 200: `trovato` dice se il produttore esiste, `attivo` se è convenzionato.
     */
    public function show(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tel' => ['required', 'string', 'max:40'],
        ]);

        $wanted = self::nationalNumber($validated['tel']);

        if (strlen($wanted) < 6) {
            return response()->json(['trovato' => false]);
        }

        $producer = Fornitore::query()->whereNotNull('tel')
            ->get(['id', 'name', 'nome', 'tel', 'email', 'cf', 'piva', 'sigla', 'codice', 'company_id', 'is_active'])
            ->filter(fn (Fornitore $fornitore): bool => self::nationalNumber((string) $fornitore->tel) === $wanted)
            ->sortBy(fn (Fornitore $fornitore): int => $fornitore->is_active ? 0 : 1)
            ->first();

        if ($producer === null) {
            return response()->json(['trovato' => false]);
        }

        return response()->json([
            'trovato' => true,
            'produttore' => [
                'id' => $producer->getKey(),
                'denominazione' => $producer->name,
                'referente' => $producer->nome,
                'email' => $producer->email,
                'codice_fiscale' => $producer->cf,
                'partita_iva' => $producer->piva,
                'sigla' => $producer->sigla,
                'codice' => $producer->codice,
                'societa_partita_iva' => self::companyVatNumber($producer->company_id),
                'attivo' => (bool) $producer->is_active,
            ],
        ]);
    }

    /** Partita IVA della società del produttore (solo cifre): unicoagent la confronta con le sue società per agganciare quella giusta. */
    private static function companyVatNumber(int|string|null $companyId): ?string
    {
        if ($companyId === null) {
            return null;
        }

        $company = Company::query()->find($companyId, ['id', 'vat_number', 'piva']);
        $vat = preg_replace('/\D+/', '', (string) ($company?->vat_number ?: $company?->piva));

        return $vat === '' || $vat === null ? null : $vat;
    }

    /** Ultime 10 cifre del numero, senza prefisso internazionale né separatori. */
    private static function nationalNumber(string $number): string
    {
        $digits = preg_replace('/\D+/', '', $number) ?? '';

        return strlen($digits) > 10 ? substr($digits, -10) : $digits;
    }
}
