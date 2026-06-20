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
        Schema::create('clienti_oam', function (Blueprint $table) {
            $table->id();  // int NOT NULL AUTO_INCREMENT

            // char(36) per l'UUID della mandataria
            $table->char('clienti_id', 36)->nullable()->comment('mandataria');

            // bigint unsigned per il codice OAM
            $table->foreignId('oam_code_id')->nullable()->comment('codice OAM');

            $table->date('dal')->nullable()->comment('data inizio convenzione');
            $table->date('al')->nullable()->comment('data fine convenzione');
            $table->timestamps();  // created_at e updated_at

            // Indici e Vincoli di Chiave Esterna
            $table
                ->foreign('clienti_id')
                ->references('id')
                ->on('clientis')
                ->onDelete('set null');  // Sicurezza in caso di eliminazione del cliente

            $table
                ->foreign('oam_code_id')
                ->references('id')
                ->on('oam_codes')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clienti_oam');
    }
};
