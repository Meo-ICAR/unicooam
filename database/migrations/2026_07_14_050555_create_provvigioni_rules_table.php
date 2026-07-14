<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('provvigioni_rules', function (Blueprint $table) {
            $table->id();

            // --- Gerarchia e Applicabilità ---
            $table->foreignId('tipo_prodotto_id')
                ->nullable()
                ->constrained('tipo_prodottos')
                ->nullOnDelete()
                ->comment('ID Prodotto (es. Cessione del Quinto). Se NULL, si applica a tutti i prodotti.');

            $table->foreignId('tipo_prodotto_sub_id')
                ->nullable()
                ->constrained('tipo_prodotto_subs')
                ->nullOnDelete()
                ->comment('ID Sottoprodotto (es. Pensionati). Se NULL, si applica a tutti i sottoprodotti del prodotto selezionato.');

            $table->foreignUuid('clienti_id')
                ->nullable()
                ->constrained('clientis')
                ->nullOnDelete()
                ->comment('ID Banca/Istituto erogante. Se NULL, si applica a tutte le banche.');

            $table->foreignUuid('fornitori_id')
                ->nullable()
                ->constrained('fornitoris')
                ->nullOnDelete()
                ->comment('ID agente. Se NULL, si applica a tutti');

            $table->foreignId('kind_id')
                ->nullable()
                ->constrained('kinds')
                ->nullOnDelete()
                ->comment('ID Ruolo/Livello dell\'agente (es. Senior, Junior). Se NULL, si applica a tutta la rete.');

            $table->boolean('coordinamento')->default(false)->comment('Provv di coordinamento');
            $table->boolean('iscliente')->default(false)->comment('Provv di mediazione da cliente');
            // --- Tipo di Calcolo ---
            $table->string('tipo_provvigioni', 30)
                ->default('lordo')
                ->comment('Tipo di calcolo: lordo, erogato, netto, fixed (importo fisso)');

            // --- Valore Economico ---
            $table->decimal('value', 10, 4)
                ->comment('Valore della provvigione. Può essere una percentuale (es. 2.5000 per 2.5%) o un importo fisso in euro.');

            // --- Gestione Storicità ---
            $table->date('valid_from')
                ->nullable()
                ->comment('Inizio validità temporale di questa specifica tariffa.');

            $table->date('valid_to')
                ->nullable()
                ->comment('Fine validità temporale di questa specifica tariffa.');

            $table->text('notes')
                ->nullable()
                ->comment('Note interne o chiarimenti sulla specifica regola provvigionale.');

            $table->timestamps();

            // Indici
            $table->index(['product_id', 'subproduct_id', 'bank_id'], 'idx_commission_hierarchy');
            $table->index(['valid_from', 'valid_to'], 'idx_commission_validity');

            // Commento della tabella stessa (Supportato nativamente da MySQL)
            $table->comment('Matrice delle regole provvigionali. Gestisce la gerarchia a cascata (Prodotto -> Sottoprodotto -> Banca).');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provvigioni_rules');
    }
};
