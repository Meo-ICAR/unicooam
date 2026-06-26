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
        Schema::create('document_schedules', function (Blueprint $table) {
            $table->id();
            // Chiave esterna sul documento reale
            $table->foreignUuid('document_id')->constrained()->cascadeOnDelete()->nullable();
            $table->uuidMorphs('documentable')->nullable();
            $table->string('entity_name');  // Nome leggibile del Soggetto (es: "Mario Rossi")
            // Raggruppamento pulito per il codice (ex alias stringa)
            $table->string('documentable_group_key');  // es: "employee|uuid"

            // Campi piatti salvati per Filament (Zero JOIN o relazioni polimorfiche a runtime)
            $table->string('document_name');  // Nome del documento
            $table->string('document_type_name');  // Nome del tipo di documento

            // Campi di controllo per scadenze e solleciti
            $table->date('expires_at')->nullable();
            $table->integer('days_until_expiry');
            $table->string('status');
            $table->integer('reminders_count')->default(0);

            $table->timestamps();

            // Indici per velocizzare Filament
            $table->index(['expires_at', 'status']);
            $table->index('documentable_group_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_schedules');
    }
};
