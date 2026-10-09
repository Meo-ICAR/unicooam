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
        Schema::table('oam_semestrales', function (Blueprint $table) {
            if (! Schema::hasColumn('oam_semestrales', 'abi_name')) {
                $table->string('abi_name', 255)->nullable()->after('prodotto_creditizio');
            }
            if (! Schema::hasColumn('oam_semestrales', 'is_convenzione')) {
                $table->boolean('is_convenzione')->default(true)->comment('Convenzione SI/NO');
            }
            if (! Schema::hasColumn('oam_semestrales', 'submission_type')) {
                $table->string('submission_type', 255)->nullable()->after('abi_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('oam_semestrales', function (Blueprint $table) {
            //
        });
    }
};
