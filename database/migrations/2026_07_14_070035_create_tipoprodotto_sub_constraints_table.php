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
        Schema::create('tipoprodotto_sub_constraints', function (Blueprint $table) {
            $table->id();

            // --- Relazione con la Banca (UUID) ---
            $table->foreignUuid('clienti_id')
                ->constrained('clienti')
                ->cascadeOnDelete()
                ->comment('L\'istituto di credito che impone il vincolo.');

            // --- Ambito di Applicazione (Gerarchico) ---
            $table->foreignId('tipoprodotto_id')
                ->nullable()
                ->constrained('tipoprodotto')
                ->nullOnDelete()
                ->comment('Prodotto a cui si applica il vincolo. Se NULL, si applica a tutti i prodotti della banca.');

            $table->foreignId('tipoprodotto_sub_id')
                ->nullable()
                ->constrained('tipoprodotto_sub')
                ->nullOnDelete()
                ->comment('Sottoprodotto specifico (es. CQS Pensionati). Se NULL, si applica a tutto il macro-prodotto.');

            // --- Parametri di Vincolo Strutturati (Colonne Standard) ---
            $table->integer('min_age')->nullable()->comment('Età minima del richiedente alla firma.');
            $table->integer('max_age_at_maturity')->nullable()->comment('Età massima consentita alla scadenza del finanziamento.');
            $table->decimal('min_amount', 12, 2)->nullable()->comment('Importo minimo erogabile.');
            $table->decimal('max_amount', 12, 2)->nullable()->comment('Importo massimo erogabile.');
            $table->integer('min_duration_months')->nullable()->comment('Durata minima in mesi.');
            $table->integer('max_duration_months')->nullable()->comment('Durata massima in mesi.');
            $table->integer('min_employment_months')->nullable()->comment('Anzianità lavorativa minima richiesta.');
            $table->decimal('max_debt_to_income_ratio', 5, 2)->nullable()->comment('Rapporto rata/reddito massimo % (es. 33.33).');
            $table->decimal('max_ltv_percentage', 5, 2)->nullable()->comment('LTV massimo consentito per mutui %.');
            $table->json('allowed_employment_types')->nullable()->comment('Tipi di impiego accettati JSON (es. ["indeterminato", "pensionato"]).');

            // --- IL NUOVO CAMPO JSON PER VINCOLI CUSTOM/EXTRA ---
            $table->json('additional_rules_json')
                ->nullable()
                ->comment('Ulteriori vincoli dinamici in formato chiave-valore. Es: {"requires_co_signer": true, "blocked_ateco_codes": ["96.09", "92.00"], "crif_allowed": false}');

            // --- Note descrittive testuali ---
            $table->text('additional_notes')
                ->nullable()
                ->comment('Eventuali note descrittive testuali dei vincoli per l\'operatore.');

            $table->timestamps();
            $table->softDeletes()->comment('Data di eliminazione logica del vincolo (Soft Delete).');

            // Indici
            $table->index(['bank_id', 'tipoprodotto_id', 'tipoprodotto_sub_id'], 'idx_bank_product_constraints');

            $table->comment('Vincoli assuntivi e limiti operativi imposti dalle banche sui prodotti con estensibilità JSON.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipoprodotto_sub_constraints');
    }
};
