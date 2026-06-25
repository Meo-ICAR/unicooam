<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audits', function (Blueprint $table) {
            $table->comment('Registro degli audit interni ed esterni gestiti dal mediatore');
            $table->id();

            // Identificativo del tenant / azienda principale
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();

            // FIX: Usiamo uuidMorphs perché i soggetti (es. Company o Clienti) usano chiavi UUID
            $table->uuidMorphs('auditable');

            // Date: separiamo pianificata ed effettiva per calcolare i ritardi
            $table->date('scheduled_at')->nullable();
            $table->date('executed_at')->nullable();

            // Stato dell'avanzamento (gestito tramite PHP Enum)
            $table->string('status')->default('planned');

            $table->string('auditor')->nullable();
            $table->text('auditor_notes')->nullable();
            $table->string('remediation_plan')->nullable();
            $table->date('followup_date')->nullable();
            // Numero protocollo: fondamentale per tracciabilità ispezioni OAM / Autorità
            $table->string('protocol_number')->nullable()->unique();

            // Origine/Direzione dell'audit (Es. Interno, In entrata, In uscita)
            $table->string('origin_type')->default('internal');

            // Modalità di esecuzione (Es. Documentale, In Loco/Ispezione, Schedulato)
            $table->string('execution_method')->default('documentale');

            // Ente Vigilante / Soggetto terzo che richiede o esegue l'audit (OAM, Banca d'Italia, IVASS, ecc.)
            // Diventa stringa libera o slug gestito da PHP Enum, molto più flessibile
            $table->string('authority_type')->nullable();
            $table->string('authority_name')->nullable();

            // Dati descrittivi

            $table->text('scope')->nullable();

            // Esito finale dell'audit: aiuta a fare reportistica immediata (es. Superato, Con Rilievi, Fallito)
            $table->string('outcome')->nullable();

            // Note e sintesi
            $table->text('summary')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // FIX: Rimosso il vincolo fisso su 'mysql' per garantire compatibilità con i test (es. SQLite in-memory)
        Schema::dropIfExists('audits');
    }
};
