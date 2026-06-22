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
        Schema::table('branches', function (Blueprint $table) {
            $table->string('address', 255)->nullable()->after('name')
                ->comment('Via/Piazza della sede');
            $table->string('street_number', 20)->nullable()->after('address')
                ->comment('Numero civico della sede');
            $table->string('city', 100)->nullable()->after('street_number')
                ->comment('Città della sede');
            $table->string('zip_code', 10)->nullable()->after('city')
                ->comment('CAP della sede');
            $table->string('province', 100)->nullable()->after('zip_code')
                ->comment('Provincia della sede (nome esteso)');
            $table->string('region', 100)->nullable()->after('province')
                ->comment('Regione della sede');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['address', 'street_number', 'city', 'zip_code', 'province', 'region']);
        });
    }
};
