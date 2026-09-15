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
        Schema::table('clienti_oam', function (Blueprint $table) {
            $table->unique(['clienti_id', 'oam_code_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clienti_oam', function (Blueprint $table) {
            $table->dropUnique(['clienti_id', 'oam_code_id']);
        });
    }
};
