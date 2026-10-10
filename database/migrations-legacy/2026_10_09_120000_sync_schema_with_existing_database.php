<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Allinea le migration allo schema reale del database di sviluppo.
 * Ogni colonna e' aggiunta solo se manca: sul database esistente e' un no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->addMissing('audits', function (Blueprint $table, callable $has) {
            $has('followup_checked') || $table->date('followup_checked')->nullable();
            $has('followup_notes') || $table->text('followup_notes')->nullable();
            $has('drive_folder_id') || $table->string('drive_folder_id')->nullable();
        });

        $this->addMissing('complaint_registry', function (Blueprint $table, callable $has) {
            $has('data_subject_request_id') || $table->unsignedBigInteger('data_subject_request_id')->nullable();
            $has('event_sequence') || $table->unsignedInteger('event_sequence')->nullable();
            $has('event_at') || $table->dateTime('event_at')->nullable();
            foreach ([
                'event_phase', 'mandating_company', 'master_agency', 'sub_supplier', 'caller_number',
                'agcom_roc_compliance', 'event_channel_label', 'event_direction', 'event_counterparty',
                'complainant_phone', 'complainant_fiscal_code',
            ] as $column) {
                $has($column) || $table->string($column)->nullable();
            }
            $has('operational_action') || $table->text('operational_action')->nullable();
            $has('dnc_blacklist_status') || $table->string('dnc_blacklist_status')->nullable();
            $has('sla_deadline_note') || $table->string('sla_deadline_note')->nullable();
            $has('log_freeze_retention') || $table->text('log_freeze_retention')->nullable();
            $has('evidence_attachment') || $table->string('evidence_attachment')->nullable();
            $has('phase_status') || $table->string('phase_status')->nullable();
            $has('assigned_to') || $table->string('assigned_to')->nullable();
        });

        $this->addMissing('email_templates', function (Blueprint $table, callable $has) {
            $has('emailable') || $table->string('emailable')->nullable();
            $has('email_field') || $table->string('email_field')->nullable()->comment('Campo del modello contenente l\'email del sender');
            $has('trigger_field') || $table->string('trigger_field')->nullable()->comment('Campo del modello da controllare');
            $has('trigger_state') || $table->string('trigger_state')->nullable()->comment('filled, empty, equals');
            $has('trigger_value') || $table->string('trigger_value')->nullable()->comment('Il valore specifico da controllare');
        });

        $this->addMissing('employees', function (Blueprint $table, callable $has) {
            $has('employee_roles') || $table->json('employee_roles');
            $has('is_external') || $table->boolean('is_external')->default(false);
        });

        $this->addMissing('oam_codes', function (Blueprint $table, callable $has) {
            $has('submission_type') || $table->string('submission_type')->nullable();
        });

        $this->addMissing('oam_semestrales', function (Blueprint $table, callable $has) {
            $has('gestione') || $table->string('gestione')->nullable();
        });

        $this->addMissing('tipo_prodottos', function (Blueprint $table, callable $has) {
            $has('name') || $table->string('name');
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->string('branchable_type')->nullable(false)->change();
            $table->char('branchable_id', 36)->nullable(false)->change();
        });

        Schema::table('company_roles', function (Blueprint $table) {
            $table->string('funzione')->nullable()->change();
            $table->string('execution_method')->nullable()->change();
        });

        Schema::table('employee_types', function (Blueprint $table) {
            $table->boolean('is_external')->nullable()->default(false)->change();
        });
    }

    public function down(): void
    {
        //
    }

    /**
     * @param  Closure(Blueprint, callable(string): bool): void  $callback
     */
    private function addMissing(string $tableName, Closure $callback): void
    {
        Schema::table($tableName, function (Blueprint $table) use ($tableName, $callback) {
            $callback($table, fn (string $column): bool => Schema::hasColumn($tableName, $column));
        });
    }
};
