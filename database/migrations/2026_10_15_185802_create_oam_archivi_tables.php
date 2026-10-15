<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oam_archivi', function (Blueprint $table) {
            $table->id()->comment('ID intero autoincrementante');
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete()->comment('Azienda (tenant) proprietaria dell\'archivio');
            $table->char('period', 8)->comment('Semestre di riferimento (come oam_semestrales.period)');
            $table->string('note')->nullable()->comment('Nota libera sull\'archiviazione');
            $table->foreignId('archived_by')->nullable()->comment('Utente che ha storicizzato');
            $table->unsignedInteger('semestrali_count')->default(0)->comment('Righe di oam_semestrales archiviate');
            $table->unsignedInteger('pratiche_count')->default(0)->comment('Righe di oam_pratiches archiviate');
            $table->timestamp('restored_at')->nullable()->comment('Ultimo richiamo dell\'archivio nelle tabelle di lavoro');
            $table->timestamp('created_at')->nullable()->comment('Data e ora di archiviazione');
            $table->timestamp('updated_at')->nullable();
            $table->index(['company_id', 'period']);
            $table->comment('Storicizzazione OAM: una riga per ogni archiviazione di un semestre.');
        });

        Schema::create('oam_archivio_righe', function (Blueprint $table) {
            $table->id()->comment('ID intero autoincrementante');
            $table->foreignId('oam_archivio_id')->constrained('oam_archivi')->cascadeOnDelete()->comment('Archivio di appartenenza');
            $table->string('tipo', 20)->comment('semestrale = oam_semestrales, pratica = oam_pratiches');
            $table->json('dati')->comment('Riga originale (senza id), così com\'era al momento dell\'archiviazione');
            $table->index(['oam_archivio_id', 'tipo']);
            $table->comment('Righe archiviate di oam_semestrales e oam_pratiches.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oam_archivio_righe');
        Schema::dropIfExists('oam_archivi');
    }
};
