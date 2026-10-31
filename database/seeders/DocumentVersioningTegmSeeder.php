<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Collega la vecchia versione del documento TEGM (id 20) a quella attuale (id 18) come se il rinnovo
 * l'avesse fatto Roberto Perna: la vecchia passa a «scaduto» e punta alla nuova con `replaced_by_id` e `renewed_by_id`,
 * `renewed_by` è l'utente. Si può rilanciare senza effetti: non fa nulla se il legame c'è già.
 *
 * php artisan db:seed --class=DocumentVersioningTegmSeeder
 */
class DocumentVersioningTegmSeeder extends Seeder
{
    private const OLD_DOCUMENT_ID = 20;

    private const CURRENT_DOCUMENT_ID = 18;

    private const RENEWED_BY_EMAIL = 'roberto.perna@races.it';

    public function run(): void
    {
        $renewer = User::query()->where('email', self::RENEWED_BY_EMAIL)->first();
        $old = Document::query()->find(self::OLD_DOCUMENT_ID);
        $current = Document::query()->find(self::CURRENT_DOCUMENT_ID);

        if ($renewer === null || $old === null || $current === null) {
            $this->command?->warn('Utente '.self::RENEWED_BY_EMAIL.' o documenti '.self::OLD_DOCUMENT_ID.'/'.self::CURRENT_DOCUMENT_ID.' non trovati: nessuna modifica.');

            return;
        }

        if ((int) $old->renewed_by_id === self::CURRENT_DOCUMENT_ID) {
            $this->command?->info('Il documento '.self::OLD_DOCUMENT_ID.' è già collegato al '.self::CURRENT_DOCUMENT_ID.'.');

            return;
        }

        if ((int) $old->document_type_id !== (int) $current->document_type_id) {
            $this->command?->warn('I due documenti sono di tipo diverso: nessuna modifica.');

            return;
        }

        // Query diretta: il salvataggio del modello ricalcolerebbe stato, scadenza e «in carico a».
        DB::connection($old->getConnectionName())->table('documents')
            ->where('id', $old->id)
            ->update([
                'status' => 'scaduto',
                'replaced_by_id' => $current->id,
                'renewed_by_id' => $current->id,
                'renewed_by' => $renewer->id,
                'updated_by' => $renewer->id,
                'updated_at' => now(),
            ]);

        $this->command?->info('Documento '.$old->id.' reso versione precedente del '.$current->id.' (rinnovo di '.$renewer->name.').');
    }
}
