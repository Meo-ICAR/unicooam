<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Catalogo degli Stati Pratica (27 stati con flag e ordine)
        Schema::create('pratica_stati', function (Blueprint $table) {
            $table->id()
                ->comment('ID univoco dello stato');

            $table->string('codice')->unique()
                ->comment('Codice univoco dello stato (es. caricata_banca, declinata)');

            $table->string('name')
                ->comment('Label dell\'esito/stato visibile agli utenti in interfaccia');

            $table->integer('ordine')->default(0)
                ->comment('Numero d\'ordine sequenziale per il vincolo vettoriale (nuovo_ordine >= ordine_attuale)');

            // Flag di logica di business
            $table->boolean('is_rejected')->default(false)
                ->comment('Flag: true se identifica uno stato di rifiuto, declinazione o rinuncia');

            $table->boolean('is_working')->default(true)
                ->comment('Flag: true se la pratica è in corso di lavorazione/istruttoria');

            $table->boolean('is_estingued')->default(false)
                ->comment('Flag: true se la pratica è conclusa/estinta/rinnovabile');

            // Dati di stile per l'interfaccia Filament
            $table->string('colore')->default('gray')
                ->comment('Colore badge per Filament (gray, success, danger, warning, info)');

            $table->string('icona')->nullable()
                ->comment('Nome icona Heroicon per la rappresentazione visiva');

            $table->timestamps();
            $table->softDeletes()
                ->comment('Data di dismissione dello stato per mantenere lo storico');
        });

        // 2. Tabella Transizioni (Matrice per eccezioni / sovrascritture di flusso)
        Schema::create('pratica_stati_transizioni', function (Blueprint $table) {
            $table->id()
                ->comment('ID univoco della transizione');

            $table->foreignId('stato_da_id')
                ->constrained('pratica_stati')
                ->cascadeOnDelete()
                ->comment('FK verso lo stato di partenza');

            $table->foreignId('stato_a_id')
                ->constrained('pratica_stati')
                ->cascadeOnDelete()
                ->comment('FK verso lo stato di destinazione consentito');

            $table->unique(['stato_da_id', 'stato_a_id'], 'unique_stato_transizione');
        });

        // 3. Catalogo Requisiti Paralleli (Polizza Vita, Polizza Impiego, Benestare, Perizia, ecc.)
        Schema::create('pratica_requisiti', function (Blueprint $table) {
            $table->id()
                ->comment('ID univoco del requisito a catalogo');

            $table->string('codice')->unique()
                ->comment('Codice univoco del requisito (es. polizza_vita, certificato_stipendio)');

            $table->string('name')
                ->comment('Nome descrittivo del requisito/task parallelo');

            $table->text('descrizione')->nullable()
                ->comment('Descrizione o istruzioni operative per l\'evasione del requisito');

            $table->timestamps();
        });

        // 4. Pivot Requisiti per Tipo Finanziamento (Configurazione Requisiti per Prodotto)
        Schema::create('requisito_tipo_finanziamento', function (Blueprint $table) {
            $table->id()
                ->comment('ID univoco della regola di associazione');

            $table->unsignedBigInteger('tipoprodotto_id')->nullable()
                ->comment('Prodotto a cui si applica il vincolo (DB Esterno). Se NULL, si applica a tutti i prodotti');

            $table->unsignedBigInteger('tipoprodotto_sub_id')->nullable()
                ->comment('Sottoprodotto specifico (es. CQS Pensionati). Se NULL, si applica a tutto il macro-prodotto');

            $table->foreignId('pratica_requisito_id')
                ->constrained('pratica_requisiti')
                ->cascadeOnDelete()
                ->comment('FK verso il requisito a catalogo');

            $table->boolean('obbligatorio')->default(true)
                ->comment('Flag: true se il requisito è obbligatorio per l\'avanzamento');

            $table->integer('ordine')->default(0)
                ->comment('Ordinamento di visualizzazione del requisito nella checklist del prodotto');

            $table->unique(['tipoprodotto_id', 'tipoprodotto_sub_id', 'pratica_requisito_id'], 'req_tf_unique');
        });

        // 5. Tabella Operativa Requisiti Paralleli sulla Singola Pratica (Istanza Requisito)
        Schema::create('pratica_requisiti_operativi', function (Blueprint $table) {
            $table->id()
                ->comment('ID univoco dell\'istanza del requisito sulla pratica');

            $table->foreignId('pratica_id')
                ->comment('FK verso la pratica di riferimento');

            $table->foreignId('pratica_requisito_id')
                ->constrained('pratica_requisiti')
                ->restrictOnDelete()
                ->comment('FK verso il tipo di requisito da evadere');

            // Stato del singolo task parallelo
            $table->string('stato')->default('da_richiedere')
                ->comment('Stato del task: da_richiedere, richiesto, approvato, rifiutato, non_necessario');

            $table->timestamp('data_richiesta')->nullable()
                ->comment('Data e ora in cui il requisito è stato richiesto al cliente/ente');

            $table->timestamp('data_completamento')->nullable()
                ->comment('Data e ora di approvazione o evasione del requisito');

            $table->text('note')->nullable()
                ->comment('Note operative o dettagli sulle problematiche del requisito');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pratica_requisiti_operativi');
        Schema::dropIfExists('requisito_tipo_finanziamento');
        Schema::dropIfExists('pratica_requisiti');
        Schema::dropIfExists('pratica_stati_transizioni');
        Schema::dropIfExists('pratica_stati');
    }
};
