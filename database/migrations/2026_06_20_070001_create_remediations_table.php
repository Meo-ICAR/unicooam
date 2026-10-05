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
        Schema::create('remediations', function (Blueprint $table) {
            $table->comment('Registro dettaglio dei rimedi obbligatori per le anomalie rilevate');
            $table->id();
            $table->enum('remediation_type', ['AML', 'Gestione Reclami', 'Monitoraggio Rete', 'Privacy', 'Trasparenza', 'Assetto Organizzativo'])->nullable()->comment('categorizzare il rimedio');
            $table->string('name')->comment('nome rimedio');
            $table->string('code')->nullable()->comment('codice rimedio');
            $table->text('description')->nullable();
            $table->integer('timeframe_hours')->nullable();
            $table->string('timeframe_desc')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('remediation_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remediations');
    }
};
