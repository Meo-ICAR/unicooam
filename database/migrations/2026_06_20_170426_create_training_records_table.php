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
        Schema::create('training_records', function (Blueprint $table) {
            $table->id();

            // Chiave esterna verso l'azienda (UUID/Char 36)
            $table->char('company_id', 36);

            // Relazione polimorfica (Genera trainable_type, trainable_id e l'indice composto)
            $table->numericMorphs('trainable');

            // Enum Ambiti Regolatori
            $table->enum('regulatory_framework', [
                'gdpr', 'oam', 'ivass', 'sicurezza_lavoro', 'antiriciclaggio', 'mifid', 'other'
            ]);

            $table->string('course_title');
            $table->text('course_description')->nullable();
            $table->string('provider')->nullable();
            $table->string('trainer')->nullable();

            // Enum Modalità di Erogazione
            $table->enum('delivery_mode', [
                'in_person', 'online', 'blended', 'on_the_job', 'webinar'
            ])->default('in_person');

            $table->date('training_date');
            $table->date('expiry_date')->nullable();
            $table->decimal('hours', 5, 1)->default(0.0);

            // Enum Esito
            $table->enum('outcome', [
                'passed', 'failed', 'attended', 'partial'
            ])->default('attended');

            $table->decimal('score', 5, 2)->nullable();
            $table->boolean('certificate_issued')->default(false);
            $table->string('certificate_number')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();  // Abilita il deleted_at

            // Vincolo di integrità per la Company
            $table
                ->foreign('company_id')
                ->references('id')
                ->on('companies')  // Assicurati che la tabella delle aziende si chiami così
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_records');
    }
};
