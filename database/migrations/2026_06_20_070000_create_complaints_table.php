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
        Schema::create('complaints', function (Blueprint $table) {
            $table->comment('Registro delle segnalazioni e reclami ricevuti');
            $table->id();
            $table->uuid('company_id')->comment('Logical FK: db_bpm.companies');
            $table->morphs('complaintable');  // _type + _id
            $table->date('received_at');
            $table->string('subject');
            $table->text('description');
            $table->string('status')->comment('Enum: ricevuto, in_lavorazione, accolto, respinto');
            $table->date('resolved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('received_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_compliance')->dropIfExists('complaints');
    }
};
