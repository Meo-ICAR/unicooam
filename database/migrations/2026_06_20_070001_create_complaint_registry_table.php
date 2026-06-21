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
        Schema::create('complaint_registry', function (Blueprint $table) {
            $table->comment('Registro dettaglio delle segnalazioni e reclami ricevuti');
            $table->id();
            $table->uuid('company_id')->comment('Logical FK: db_bpm.companies');
            $table->string('name')->comment('Name of the person or entity making the complaint');
            $table->date('received_at');
            $table->string('company_email')->comment('Email of the company that received the complaint');
            $table->string('category', 50)->comment('Enum: delay, behavior, privacy, fraud');
            $table->string('request_type')->comment('Type of request Enum: investigation, access, rectification, erasure, portability');
            $table->text('request_details');
            $table->string('complaint_number', 50)->unique();
            $table->string('complaint_email')->comment('Email of the person or entity making the complaint');

            $table->string('complaint_type')->comment('Identifier for the  model of the complaint OAM / Client / Fornitore / Employee / Clienti ');
            $table->string('complaint_id')->comment('Identifier for the complaint');

            $table->text('description');
            $table->string('entity_type')->comment('Identifier for the  model destination of the complaint pratica / client / fornitori / employee');
            $table->string('entity_id')->comment('Identifier for destination of the complaint');
            $table->decimal('financial_impact', 10, 2)->default(0.0);
            $table->string('status', 30)->comment('Enum: open, investigating, resolved, rejected');

            $table->timestamp('deadline_at')->nullable();
            $table->boolean('is_extended')->default(false);
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // $table->index('company_id', 'idx_complaint_company');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_compliance')->dropIfExists('complaint_registry');
    }
};
