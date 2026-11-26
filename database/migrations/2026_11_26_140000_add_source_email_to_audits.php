<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('audits')) {
            return;
        }

        if (! Schema::hasColumn('audits', 'source_email_id')) {
            Schema::table('audits', function (Blueprint $table) {
                $table->unsignedBigInteger('source_email_id')->nullable()->after('quality_review_id')->comment('Email di UnicoPEC da cui nasce l\'audit (es. verifiche ispettive periodiche comunicate dal legale)');
                $table->timestamp('notified_at')->nullable()->after('source_email_id')->comment('Quando la comunicazione dell\'audit è stata inviata al soggetto controllato');
                $table->unique(['source_email_id', 'auditable_type', 'auditable_id'], 'audits_source_email_auditable_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('audits', 'source_email_id')) {
            Schema::table('audits', function (Blueprint $table) {
                $table->dropUnique('audits_source_email_auditable_unique');
                $table->dropColumn(['source_email_id', 'notified_at']);
            });
        }
    }
};
