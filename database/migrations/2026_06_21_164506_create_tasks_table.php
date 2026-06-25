<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();  // OnBoarding, Renew, Audit
            $table->text('description')->nullable();
            $table->string('taskable')->nullable();  // Modello
            $table->string('trigger_field')->nullable()->comment('Campo del modello da controllare');
            $table->string('trigger_state')->nullable()->comment('filled, empty, equals');
            $table->string('trigger_value')->nullable()->comment('Il valore specifico da controllare');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
