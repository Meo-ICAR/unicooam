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
        Schema::create('company_ispections', function (Blueprint $table) {
            $table->comment('Audit previsti nel periodo');
            $table->id();

            $table
                ->char('company_id', 36)
                ->comment('ID di collegamento alla tabella companies (formato UUID/Char)');

            $table
                ->string('name', 255)
                ->nullable()
                ->comment("Nome o descrizione descrittiva dell'ispezione");

            $table
                ->date('dal')
                ->nullable()
                ->comment('Data di inizio del periodo di ispezione');

            $table
                ->date('al')
                ->nullable()
                ->comment('Data di fine del periodo di ispezione');

            $table
                ->enum('execution_method', ['documentale', '', 'onsite'])
                ->nullable()
                ->default('')
                ->comment('Metodo di esecuzione: tramite documenti, non specificato o sul posto');

            $table
                ->string('ispectorName', 255)
                ->nullable()
                ->comment("Nome e cognome dell'ispettore che esegue il controllo");

            $table
                ->integer('n')
                ->nullable()
                ->comment('Numero identificativo interno o contatore progressivo');

            // Definizione della Foreign Key
            $table
                ->foreign('company_id')
                ->references('id')
                ->on('companies');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_ispections');
    }
};
