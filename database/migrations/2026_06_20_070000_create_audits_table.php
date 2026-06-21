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
            $table->comment('Registro degli audit interni ed esterni alle aziende');
            $table->id();

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();

            // Chi viene auditato — polimorfico: Client (responsabile del trattamento) o Company stessa
            $table->morphs('auditable');  // auditable_type + auditable_id

            // Direzione dell'audit
            $table->enum('audit_type', [
                'outgoing',  // Company audita un suo Client (responsabile del trattamento)
                'incoming',  // Company riceve audit da autorità o da Client che l'ha nominata responsabile
                'documentale',  // Company audita un suo Produttore
                'ispezione',  // Company ispeziona in sede produttore
            ]);

            // Origine per audit incoming
            $table->enum('authority_type', [
                'garante',  // Garante Privacy (GPDP)
                'oam',  // OAM
                'ivass',  // IVASS
                'banca_italia',  // Banca d'Italia
                'client',  // Client che ha nominato la company come responsabile
                'internal',  // Audit interno
                'other',
            ])->nullable();

            $table->string('authority_name')->nullable();  // Nome specifico autorità/cliente richiedente

            // Dati audit
            $table->string('title');
            $table->text('scope')->nullable();  // Perimetro / oggetto dell'audit
            $table->date('audit_date');
            $table->date('followup_date')->nullable();  // Data prevista follow-up

            $table->enum('status', [
                'planned',  // Pianificato
                'in_progress',  // In corso
                'completed',  // Completato (senza rilievi o rilievi chiusi)
                'pending_followup',  // In attesa di follow-up
            ])->default('planned');

            $table->text('summary')->nullable();  // Sintesi dell'audit
            $table->text('auditor_notes')->nullable();  // Note dell'auditor

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::connection('mysql')->dropIfExists('audits');
    }
};
