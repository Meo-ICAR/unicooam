<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blacklist_clienti_fornitori', function (Blueprint $table) {
            $table->id();
            $table->char('cliente_id', 36)->comment('L\'istituto di credito (DB Esterno)');
            $table->char('fornitore_id', 36)->comment('L\'agente bloccato');
            $table->text('motivo')->nullable()->comment('Motivazione del blocco da parte della banca');
            $table->date('data_inizio')->nullable()->comment('Data di inizio blocco');
            $table->date('data_fine')->nullable()->comment('Eventuale data di fine blocco (se temporaneo)');
            $table->timestamps();

            // Indici per velocizzare le query di verifica
            $table->unique(['cliente_id', 'fornitore_id']);

            // Se le tabelle clienti e fornitori sono sullo stesso DB, aggiungi le foreign key:
            // $table->foreign('cliente_id')->references('id')->on('clienti')->onDelete('cascade');
            // $table->foreign('fornitore_id')->references('id')->on('fornitori')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blacklist_clienti_fornitori');
    }
};
