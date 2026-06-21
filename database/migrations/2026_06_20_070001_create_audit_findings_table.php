<<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_findings', function (Blueprint $table) {
            $table->comment('Registro dei rilievi (anomalie/non conformità) emersi durante gli audit');
            $table->id();

            // Relazioni principali
            $table->foreignId('audit_id')->constrained('audits')->cascadeOnDelete();

            // Ottima la denormalizzazione del company_id per il multi-tenant (evita JOIN pesanti)
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();

            // Anomalia rilevata
            $table->string('title');
            $table->text('description');

            // Ottimizzazione: Passiamo a stringhe per usare i PHP Enums. Aggiungiamo gli indici per query veloci.
            $table->string('severity')->default('minor')->index();
            $table->string('status')->default('open')->index();

            // Approfondimento richiesto
            $table->boolean('requires_investigation')->default(false);
            $table->text('investigation_notes')->nullable();
            $table->date('investigation_deadline')->nullable();

            // Misura correttiva (Remediation)
            $table->boolean('requires_corrective_action')->default(true);
            $table->text('corrective_action_description')->nullable();

            // Suggerito dal catalogo: aggiungiamo l'indice anche qui se prevedi di farci reportistica
            $table->unsignedBigInteger('remediation_id')->nullable()->index();
            $table->date('corrective_action_deadline')->nullable();

            // Chiusura e risoluzione
            $table->date('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        // Ottimizzazione: rimozione del vincolo ->connection('mysql')
        Schema::dropIfExists('audit_findings');
    }
};
