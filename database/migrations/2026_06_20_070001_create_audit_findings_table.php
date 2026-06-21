<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_findings', function (Blueprint $table) {
            $table->comment('Registro delle anomalie rilevate durante gli audit');
            $table->id();

            $table->foreignId('audit_id')->constrained('audits')->cascadeOnDelete();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();

            // Anomalia rilevata
            $table->string('title');
            $table->text('description');

            $table->enum('severity', [
                'observation',  // Osservazione (non bloccante)
                'minor',  // Rilievo minore
                'major',  // Rilievo maggiore
                'critical',  // Critico / bloccante
            ])->default('minor');

            // Approfondimento richiesto
            $table->boolean('requires_investigation')->default(false);
            $table->text('investigation_notes')->nullable();
            $table->date('investigation_deadline')->nullable();

            // Misura correttiva
            $table->boolean('requires_corrective_action')->default(true);
            $table->text('corrective_action_description')->nullable();  // testo libero
            $table->unsignedBigInteger('remediation_id')->nullable();  // suggerito dal catalogo (mariadb, no FK)
            $table->date('corrective_action_deadline')->nullable();

            // Stato del rilievo
            $table->enum('status', [
                'open',  // Aperto
                'in_progress',  // In lavorazione
                'resolved',  // Risolto
                'accepted_risk',  // Rischio accettato
                'closed',  // Chiuso
            ])->default('open');

            $table->date('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::connection('mysql')->dropIfExists('audit_findings');
    }
};
