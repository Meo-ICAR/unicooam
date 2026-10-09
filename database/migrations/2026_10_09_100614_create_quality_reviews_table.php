<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_reviews', function (Blueprint $table) {
            $table->comment('Sessioni di controllo qualità: campione casuale di pratiche da sottoporre al responsabile qualità.');
            $table->id();
            $table->foreignUuid('company_id')->nullable()->index()->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('reviewer_user_id')->nullable()->index()->constrained('users')->nullOnDelete()
                ->comment('Utente (ruolo quality) a cui è assegnato il campione');
            $table->date('period_from')->nullable();
            $table->date('period_to')->nullable();
            $table->unsignedInteger('sample_size')->default(0);
            $table->json('filters')->nullable()->comment('Parametri usati per l\'estrazione, per tracciabilità');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('audits', function (Blueprint $table) {
            $table->foreignId('quality_review_id')->nullable()->after('company_id')->index()
                ->constrained('quality_reviews')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->dropConstrainedForeignId('quality_review_id');
        });

        Schema::dropIfExists('quality_reviews');
    }
};
