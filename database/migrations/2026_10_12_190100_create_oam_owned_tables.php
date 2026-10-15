<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('quality_reviews')) {
            Schema::create('quality_reviews', function (Blueprint $table) {
                $table->id()->comment('ID intero autoincrementante');
                $table->foreignId('company_id')->nullable()->comment('Azienda (tenant) proprietaria del record');
                $table->string('name')->comment('Titolo della revisione di qualità');
                $table->foreignId('reviewer_user_id')->nullable()->comment('Utente che esegue la revisione');
                $table->date('period_from')->nullable()->comment('Inizio del periodo esaminato');
                $table->date('period_to')->nullable()->comment('Fine del periodo esaminato');
                $table->unsignedInteger('sample_size')->default(0)->comment('Numero di pratiche o documenti campionati');
                $table->json('filters')->nullable()->comment('Parametri usati per l\'estrazione, per tracciabilità');
                $table->text('notes')->nullable()->comment('Note libere');
                $table->timestamp('created_at')->nullable()->comment('Data e ora di creazione del record');
                $table->timestamp('updated_at')->nullable()->comment('Data e ora dell\'ultima modifica del record');
                $table->timestamp('deleted_at')->nullable()->comment('Data e ora di eliminazione logica (soft delete); nullo = record attivo');
                $table->comment('Sessioni di controllo qualità: campione casuale di pratiche da sottoporre al responsabile qualità.');
            });
        }
        if (! Schema::hasTable('remediations')) {
            Schema::create('remediations', function (Blueprint $table) {
                $table->id()->comment('ID intero autoincrementante');
                $table->enum('remediation_type', ['AML', 'Gestione Reclami', 'Monitoraggio Rete', 'Privacy', 'Trasparenza', 'Assetto Organizzativo'])->nullable()->comment('categorizzare il rimedio');
                $table->string('name')->comment('nome rimedio');
                $table->string('code')->nullable()->comment('codice rimedio');
                $table->text('description')->nullable()->comment('Descrizione del rimedio');
                $table->integer('timeframe_hours')->nullable()->comment('Tempo massimo per attuarlo, in ore');
                $table->string('timeframe_desc')->nullable()->comment('Tempo massimo per attuarlo, in forma descrittiva');
                $table->timestamp('created_at')->nullable()->comment('Data e ora di creazione del record');
                $table->timestamp('updated_at')->nullable()->comment('Data e ora dell\'ultima modifica del record');
                $table->timestamp('deleted_at')->nullable()->comment('Data e ora di eliminazione logica (soft delete); nullo = record attivo');
                $table->index(['remediation_type'], 'remediations_remediation_type_index');
                $table->comment('Registro dettaglio dei rimedi obbligatori per le anomalie rilevate');
            });
        }
        if (! Schema::hasTable('audits')) {
            Schema::create('audits', function (Blueprint $table) {
                $table->id()->comment('ID intero autoincrementante');
                $table->foreignId('company_id')->nullable()->comment('Azienda (tenant) proprietaria del record');
                $table->foreignId('quality_review_id')->nullable()->comment('Revisione di qualità di cui fa parte l\'audit');
                $table->text('name')->nullable()->comment('Nome audit');
                $table->string('auditable_type')->comment('Alias del modello controllato (morph map)');
                $table->string('auditable_id', 36)->comment('ID del soggetto controllato');
                $table->index(['auditable_type', 'auditable_id']);
                $table->string('auditor_name')->nullable()->comment('Nome dell\'ispettore o dell\'auditor esterno/interno');
                $table->foreignId('organization_id')->nullable()->comment('Organizzazione che esegue o riceve l\'audit');
                $table->date('scheduled_at')->nullable()->comment('Data pianificata');
                $table->date('executed_at')->nullable()->comment('Data di esecuzione effettiva');
                $table->string('status')->default('planned')->comment('Stato: planned, in_progress, completed, cancelled');
                $table->string('protocol_number')->nullable()->comment('Numero di protocollo ufficiale (es. OAM)');
                $table->string('origin_type')->default('internal')->comment('internal o external_incoming');
                $table->string('execution_method')->default('documentale')->comment('Metodo: documentale, in_loco, intervista');
                $table->text('scope')->nullable()->comment('Ambito oggettivo del controllo');
                $table->string('outcome')->nullable()->comment('Esito: Passato, Con Rilievi, Fallito');
                $table->enum('severity', ['Alto', 'Medio', 'Basso'])->nullable()->comment('Severita rilievo');
                $table->text('summary')->nullable()->comment('Sintesi dei risultati');
                $table->text('auditor_notes')->nullable()->comment('Note riservate dell\'auditor');
                $table->text('remediation_plan')->nullable()->comment('Piano di rimedio richiesto');
                $table->date('followup_date')->nullable()->comment('Data di verifica dei rimedi');
                $table->date('followup_checked')->nullable()->comment('Data in cui è stato verificato il seguito');
                $table->text('followup_notes')->nullable()->comment('Note sul seguito dell\'audit');
                $table->json('rilievi_codes')->nullable()->comment('Codici rilievi');
                $table->string('collaboratore')->nullable()->comment('Tipologia collaboratore');
                $table->string('drive_folder_id')->nullable()->comment('Cartella Google Drive delle evidenze dell\'audit');
                $table->timestamp('created_at')->nullable()->comment('Data e ora di creazione del record');
                $table->timestamp('updated_at')->nullable()->comment('Data e ora dell\'ultima modifica del record');
                $table->timestamp('deleted_at')->nullable()->comment('Data e ora di eliminazione logica (soft delete); nullo = record attivo');
                $table->unique(['protocol_number'], 'audits_protocol_number_unique');
                $table->index(['scheduled_at'], 'audits_scheduled_at_index');
                $table->index(['executed_at'], 'audits_executed_at_index');
                $table->index(['status'], 'audits_status_index');
                $table->index(['origin_type'], 'audits_origin_type_index');
                $table->index(['outcome'], 'audits_outcome_index');
                $table->comment('Registro dei controlli (audit) eseguiti sui collaboratori/impiegati, richiesti da enti interni o esterni.');
            });
        }
        if (! Schema::hasTable('audit_findings')) {
            Schema::create('audit_findings', function (Blueprint $table) {
                $table->id()->comment('ID intero autoincrementante');
                $table->foreignId('audit_id')->comment('Audit in cui è emersa la non conformità');
                $table->foreignId('company_id')->comment('Azienda (tenant) proprietaria del record');
                $table->string('title')->comment('Titolo sintetico della non conformità');
                $table->text('description')->comment('Descrizione della non conformità');
                $table->string('severity')->default('minor')->comment('Gravità della non conformità');
                $table->string('status')->default('open')->comment('Stato di avanzamento della gestione');
                $table->boolean('requires_investigation')->default(false)->comment('Richiede un approfondimento');
                $table->text('investigation_notes')->nullable()->comment('Esito dell\'approfondimento');
                $table->date('investigation_deadline')->nullable()->comment('Scadenza dell\'approfondimento');
                $table->boolean('requires_corrective_action')->default(true)->comment('Richiede un\'azione correttiva');
                $table->text('corrective_action_description')->nullable()->comment('Descrizione dell\'azione correttiva');
                $table->foreignId('remediation_id')->nullable()->comment('Rimedio standard applicato');
                $table->date('corrective_action_deadline')->nullable()->comment('Scadenza dell\'azione correttiva');
                $table->date('resolved_at')->nullable()->comment('Data di risoluzione');
                $table->text('resolution_notes')->nullable()->comment('Note sulla risoluzione');
                $table->timestamp('created_at')->nullable()->comment('Data e ora di creazione del record');
                $table->timestamp('updated_at')->nullable()->comment('Data e ora dell\'ultima modifica del record');
                $table->timestamp('deleted_at')->nullable()->comment('Data e ora di eliminazione logica (soft delete); nullo = record attivo');
                $table->index(['severity'], 'audit_findings_severity_index');
                $table->index(['status'], 'audit_findings_status_index');
                $table->comment('Registro dei rilievi (anomalie/non conformità) emersi durante gli audit');
            });
        }
        if (! Schema::hasTable('audit_checklist_evaluations')) {
            Schema::create('audit_checklist_evaluations', function (Blueprint $table) {
                $table->id()->comment('ID intero autoincrementante');
                $table->foreignId('audit_id')->comment('Audit di riferimento');
                $table->unsignedBigInteger('audit_checklist_item_id')->comment('Riferimento debole a audit_checklist_items (connessione di default)');
                $table->unsignedBigInteger('external_processor_id')->nullable()->comment('Riferimento debole a external_processors (connessione di default)');
                $table->text('internal_documentation')->nullable()->comment('Documentazione interna/policy disponibile (Titolare/Responsabile)');
                $table->text('vendor_evidence_notes')->nullable()->comment('Evidenza caso specifico / sub-fornitore');
                $table->string('gap_status')->default('da_verificare')->comment('Enum App\\\\Enums\\\\AuditChecklistGapStatus');
                $table->text('gap_notes')->nullable()->comment('Carenze riscontrate rispetto alla voce di checklist');
                $table->boolean('is_vendor_scope')->default(false)->comment('true se il gap è attribuibile al sub-fornitore/vendor');
                $table->boolean('is_optional')->default(false)->comment('Voce facoltativa: la sua mancanza non impedisce di chiudere l\'audit');
                $table->date('verified_at')->nullable()->comment('Data della verifica della voce');
                $table->date('next_review_at')->nullable()->comment('Data della prossima verifica');
                $table->foreignId('verified_by')->nullable()->comment('Utente che ha verificato la voce');
                $table->timestamp('deleted_at')->nullable()->comment('Data e ora di eliminazione logica (soft delete); nullo = record attivo');
                $table->timestamp('created_at')->nullable()->comment('Data e ora di creazione del record');
                $table->timestamp('updated_at')->nullable()->comment('Data e ora dell\'ultima modifica del record');
                $table->unique(['audit_id', 'audit_checklist_item_id'], 'audit_checklist_eval_unique');
                $table->comment('Valutazione delle voci della checklist durante un audit');
            });
        }
        if (! Schema::hasTable('suspicious_activity_reports')) {
            Schema::create('suspicious_activity_reports', function (Blueprint $table) {
                $table->id()->comment('ID intero autoincrementante');
                $table->foreignId('company_id')->comment('Logical FK: db_bpm.companies');
                $table->string('client_id', 36)->nullable()->comment('Cliente a cui si riferisce la segnalazione');
                $table->string('reportable_type')->comment('Alias del modello oggetto della segnalazione (morph map)');
                $table->string('reportable_id', 36)->comment('ID del record oggetto della segnalazione');
                $table->index(['reportable_type', 'reportable_id']);
                $table->timestamp('reported_at')->nullable()->comment('Data e ora della segnalazione');
                $table->json('anomalies_codes')->nullable()->comment('Codici delle anomalie riscontrate (JSON)');
                $table->text('description')->comment('Descrizione dell\'operazione sospetta');
                $table->enum('status', ['pending', 'investigated', 'reported', 'archived'])->default('pending')->comment('Stato: in attesa, approfondita, segnalata all\'UIF, archiviata');
                $table->timestamp('created_at')->nullable()->comment('Data e ora di creazione del record');
                $table->timestamp('updated_at')->nullable()->comment('Data e ora dell\'ultima modifica del record');
                $table->timestamp('deleted_at')->nullable()->comment('Data e ora di eliminazione logica (soft delete); nullo = record attivo');
                $table->comment('Registro dettaglio delle segnalazioni di attività sospette');
            });
        }
        if (! Schema::hasTable('training_records')) {
            Schema::create('training_records', function (Blueprint $table) {
                $table->id()->comment('ID intero autoincrementante');
                $table->foreignId('company_id')->comment('Azienda (tenant) proprietaria del record');
                $table->string('trainable_type')->comment('Alias del modello di chi ha seguito il corso (morph map)');
                $table->string('trainable_id', 36)->comment('ID di chi ha seguito il corso');
                $table->index(['trainable_type', 'trainable_id']);
                $table->enum('regulatory_framework', ['gdpr', 'oam', 'ivass', 'sicurezza_lavoro', 'antiriciclaggio', 'mifid', 'other'])->nullable()->comment('Normativa a cui si riferisce la formazione');
                $table->string('name')->nullable()->comment('Titolo del corso');
                $table->text('description')->nullable()->comment('Contenuti e obiettivi del corso');
                $table->string('provider')->nullable()->comment('Ente che eroga il corso');
                $table->string('trainer')->nullable()->comment('Docente o formatore');
                $table->enum('delivery_mode', ['in_person', 'online', 'blended', 'on_the_job', 'webinar'])->default('in_person')->comment('Modalità di erogazione del corso');
                $table->date('training_date')->nullable()->comment('Data di svolgimento');
                $table->date('expiry_date')->nullable()->comment('Data di scadenza della validità del corso');
                $table->decimal('hours', 5, 1)->default(0.0)->comment('Ore di formazione');
                $table->enum('outcome', ['passed', 'failed', 'attended', 'partial'])->default('attended')->comment('Esito del corso');
                $table->decimal('score', 5, 2)->nullable()->comment('Punteggio ottenuto');
                $table->boolean('certificate_issued')->default(false)->comment('Indica se è stato rilasciato un attestato');
                $table->string('certificate_number')->nullable()->comment('Numero dell\'attestato');
                $table->text('notes')->nullable()->comment('Note libere');
                $table->timestamp('created_at')->nullable()->comment('Data e ora di creazione del record');
                $table->timestamp('updated_at')->nullable()->comment('Data e ora dell\'ultima modifica del record');
                $table->timestamp('deleted_at')->nullable()->comment('Data e ora di eliminazione logica (soft delete); nullo = record attivo');
                $table->comment('Registro della formazione di dipendenti e collaboratori, con esito e attestato');
            });
        }
        if (! Schema::hasTable('client_mandates')) {
            Schema::create('client_mandates', function (Blueprint $table) {
                $table->id()->comment('ID univoco mandato cliente');
                $table->foreignId('client_id')->comment('Riferimento al cliente coinvolto (mysql_proforma.clients.id)');
                $table->string('numero_mandato')->comment('Numero identificativo mandato');
                $table->date('data_firma_mandato')->comment('Innesca Instaurazione Rapporto AUI');
                $table->date('data_scadenza_mandato')->comment('Innesca Chiusura Rapporto AUI (se non erogato prima)');
                $table->decimal('importo_richiesto_mandato', 15, 2)->nullable()->comment('Importo massimo richiesto nel mandato');
                $table->string('scopo_finanziamento')->nullable()->comment('Scopo del finanziamento (es. Acquisto Prima Casa, Liquidità)');
                $table->date('data_consegna_trasparenza')->nullable()->comment('Deve essere <= data_firma');
                $table->enum('stato', ['attivo', 'concluso_con_successo', 'scaduto', 'revocato'])->default('attivo')->comment('Stato del mandato');
                $table->string('ruolo')->nullable()->comment('Ruolo del mandatario (es. delegato, tutore)');
                $table->string('name')->nullable()->comment('Descrizione');
                $table->text('notes')->nullable()->comment('Note specifiche sul ruolo per questa pratica (es. "Garante solo per quota 50%")');
                $table->text('purpose_of_relationship')->nullable()->comment('Es: Acquisto prima casa');
                $table->text('funds_origin')->nullable()->comment('Es: Risparmi, donazione, stipendio');
                $table->boolean('oam_delivered')->default(false)->comment('Foglio informativo consegnato a questo soggetto?');
                $table->enum('role_risk_level', ['basso', 'medio', 'alto'])->nullable()->comment('Livello rischio specifico ruolo nella pratica');
                $table->timestamp('deleted_at')->nullable()->comment('Data e ora di eliminazione logica (soft delete); nullo = record attivo');
                $table->timestamp('created_at')->nullable()->comment('Data e ora di creazione del record');
                $table->timestamp('updated_at')->nullable()->comment('Data e ora dell\'ultima modifica del record');
                $table->unique(['numero_mandato'], 'client_mandates_numero_mandato_unique');
                $table->comment('Mandati con cui una persona agisce per conto di un cliente');
            });
        }
        if (! Schema::hasTable('client_relations')) {
            Schema::create('client_relations', function (Blueprint $table) {
                $table->id()->comment('ID univoco relazione cliente');
                $table->foreignId('company_id')->comment('ID società persona giuridica');
                $table->foreignId('client_id')->comment('ID persona fisica cliente');
                $table->foreignId('client_type_id')->nullable()->comment('Tipo di cliente');
                $table->decimal('shares_percentage', 5, 2)->nullable()->comment('Percentuale quote possedute');
                $table->boolean('is_titolare')->default(false)->comment('Se è titolare/socio di maggioranza');
                $table->date('data_inizio_ruolo')->nullable()->comment('Data inizio ruolo');
                $table->date('data_fine_ruolo')->nullable()->comment('Data fine ruolo');
                $table->timestamp('created_at')->nullable()->comment('Data e ora di creazione del record');
                $table->timestamp('updated_at')->nullable()->comment('Data e ora dell\'ultima modifica del record');
                $table->comment('Legami tra anagrafiche clienti (familiari, soci, collegati)');
            });
        }
        if (! Schema::hasTable('blacklist_clienti_fornitori')) {
            Schema::create('blacklist_clienti_fornitori', function (Blueprint $table) {
                $table->id()->comment('ID intero autoincrementante');
                $table->string('cliente_id', 36)->comment('L\'istituto di credito (DB Esterno)');
                $table->string('fornitore_id', 36)->comment('L\'agente bloccato');
                $table->text('motivo')->nullable()->comment('Motivazione del blocco da parte della banca');
                $table->date('data_inizio')->nullable()->comment('Data di inizio blocco');
                $table->date('data_fine')->nullable()->comment('Eventuale data di fine blocco (se temporaneo)');
                $table->timestamp('created_at')->nullable()->comment('Data e ora di creazione del record');
                $table->timestamp('updated_at')->nullable()->comment('Data e ora dell\'ultima modifica del record');
                $table->unique(['cliente_id', 'fornitore_id'], 'blacklist_clienti_fornitori_cliente_id_fornitore_id_unique');
                $table->comment('Divieti di contatto tra un cliente e un fornitore');
            });
        }
        if (! Schema::hasTable('oam_pratiches')) {
            Schema::create('oam_pratiches', function (Blueprint $table) {
                $table->id()->comment('ID intero autoincrementante');
                $table->foreignId('company_id')->nullable()->comment('ID Azienda (Tenant)');
                $table->char('period', 8)->nullable()->comment('Anno e mese di riferimento del report');
                $table->string('riga')->nullable()->comment('riga');
                $table->string('prodotto_creditizio')->nullable()->comment('PRODOTTO/O CREDITIZIO OGGETTO DELLA CONVENZIONE');
                $table->string('abi_name')->nullable()->comment('Nome della banca (codice ABI)');
                $table->string('submission_type')->nullable()->comment('Tipo di invio previsto dalla segnalazione OAM');
                $table->integer('intermediari_convenzionati')->default(0)->comment('N° intermediari convenzionati');
                $table->integer('intermediari_non_convenzionati')->default(0)->comment('N° intermediari NON convenzionati');
                $table->integer('pratiche_intermediate')->default(0)->comment('N° Pratiche intermediate per prodotto/servizio');
                $table->integer('pratiche_lavorazione')->default(0)->comment('N° Pratiche di finanziamento in lavorazione');
                $table->decimal('erogato_lordo', 15, 2)->default(0.00)->comment('Montante lordo / Importo erogato per prodotto');
                $table->decimal('erogato_lavorazione', 15, 2)->default(0.00)->comment('Valore delle pratiche di finanziamento in lavorazione');
                $table->decimal('provv_clientela', 15, 2)->default(0.00)->comment('TOTALE PROVVIGIONI RICONOSCIUTE DALLA CLIENTELA');
                $table->decimal('provv_istituto_comp', 15, 2)->default(0.00)->comment('TOTALE PROVVIGIONI RICONOSCIUTE DALL ISTITUTO EROGANTE');
                $table->decimal('premi_istituto_comp', 15, 2)->default(0.00)->comment('TOTALE PREMI RICONOSCIUTI DALL ISTITUTO EROGANTE');
                $table->decimal('payin_ass_banche', 15, 2)->default(0.00)->comment('(PAY-IN) PROVV. ASSICURATIVE - da banche/intermediari');
                $table->decimal('payin_ass_broker', 15, 2)->default(0.00)->comment('(PAY-IN) PROVV. ASSICURATIVE - da Broker');
                $table->decimal('payin_ass_broker_cap', 15, 2)->default(0.00)->comment('(PAY-IN) PROVV. ASSICURATIVE - da Broker Captive');
                $table->decimal('payout_rete_credito', 15, 2)->default(0.00)->comment('AMMONTARE PROVVIGIONI RETE - INTERMEDIAZIONE CREDITO');
                $table->decimal('payout_rete_ass_banche', 15, 2)->default(0.00)->comment('(PAY-OUT) PROVV. RETE ASSICURATIVA - da banche');
                $table->decimal('payout_rete_ass_broker', 15, 2)->default(0.00)->comment('(PAY-OUT) PROVV. RETE ASSICURATIVA - da Broker');
                $table->decimal('payout_rete_ass_broker_cap', 15, 2)->default(0.00)->comment('(PAY-OUT) PROVV. RETE ASSICURATIVA - da Broker Captive');
                $table->integer('num_rivalse')->default(0)->comment('N° RIVALSE AI SENSI DELL ART. 125 - SEXIES, DEL TUB');
                $table->decimal('importo_retrocesse', 15, 2)->default(0.00)->comment('AMMONTARE DELLE PROVVIGIONI RETROCESSE AL FINANZIATORE');
                $table->string('pratica')->nullable()->comment('Codice pratica');
                $table->string('istituto')->nullable()->comment('Mandataria fatturante');
                $table->string('agente')->nullable()->comment('Agente');
                $table->string('cliente')->nullable()->comment('Cliente');
                $table->string('tipo_prodotto')->nullable()->comment('Tipo finanziamento');
                $table->string('principal_type')->nullable()->comment('Tipo istituto broker / captive');
                $table->dateTime('sended_at')->nullable()->comment('Data invio');
                $table->dateTime('approved_at')->nullable()->comment('Data delibera');
                $table->dateTime('erogated_at')->nullable()->comment('Data erogazione');
                $table->dateTime('rejected_at')->nullable()->comment('Data respinto');
                $table->dateTime('storned_at')->nullable()->comment('Data storno');
                $table->timestamp('created_at')->nullable()->comment('Data e ora di creazione del record');
                $table->timestamp('updated_at')->nullable()->comment('Data e ora dell\'ultima modifica del record');
                $table->timestamp('deleted_at')->nullable()->comment('Data e ora di eliminazione logica (soft delete); nullo = record attivo');
                $table->index(['period'], 'oam_pratiches_period_index');
                $table->comment('Dati aggregati per il report OAM: Profilo Economico/Operativo Base');
            });
        }
        if (! Schema::hasTable('oam_semestrales')) {
            Schema::create('oam_semestrales', function (Blueprint $table) {
                $table->id()->comment('ID intero autoincrementante');
                $table->foreignId('company_id')->nullable()->comment('ID Azienda (Tenant)');
                $table->char('period', 8)->nullable()->comment('Anno e mese di riferimento del report');
                $table->string('numero_iscrizione_m510')->nullable()->comment('Numero iscrizione (M510)');
                $table->string('prodotto_creditizio')->nullable()->comment('PRODOTTO/O CREDITIZIO OGGETTO DELLA CONVENZIONE');
                $table->string('abi_name')->nullable()->comment('Nome della banca (codice ABI)');
                $table->string('submission_type')->nullable()->comment('Tipo di invio previsto dalla segnalazione OAM');
                $table->string('gestione')->nullable()->comment('Tipo di gestione della segnalazione semestrale');
                $table->integer('intermediari_convenzionati')->default(0)->comment('N° intermediari convenzionati');
                $table->integer('intermediari_non_convenzionati')->default(0)->comment('N° intermediari NON convenzionati');
                $table->integer('pratiche_intermediate')->default(0)->comment('N° Pratiche intermediate per prodotto/servizio');
                $table->integer('pratiche_lavorazione')->default(0)->comment('N° Pratiche di finanziamento in lavorazione');
                $table->decimal('erogato_lordo', 15, 2)->default(0.00)->comment('Montante lordo / Importo erogato per prodotto');
                $table->decimal('erogato_lavorazione', 15, 2)->default(0.00)->comment('Valore delle pratiche di finanziamento in lavorazione');
                $table->decimal('provv_clientela', 15, 2)->default(0.00)->comment('TOTALE PROVVIGIONI RICONOSCIUTE DALLA CLIENTELA');
                $table->decimal('provv_istituto_comp', 15, 2)->default(0.00)->comment('TOTALE PROVVIGIONI RICONOSCIUTE DALL ISTITUTO EROGANTE');
                $table->decimal('premi_istituto_comp', 15, 2)->default(0.00)->comment('TOTALE PREMI RICONOSCIUTI DALL ISTITUTO EROGANTE');
                $table->decimal('payin_ass_banche', 15, 2)->default(0.00)->comment('(PAY-IN) PROVV. ASSICURATIVE - da banche/intermediari');
                $table->decimal('payin_ass_broker', 15, 2)->default(0.00)->comment('(PAY-IN) PROVV. ASSICURATIVE - da Broker');
                $table->decimal('payin_ass_broker_cap', 15, 2)->default(0.00)->comment('(PAY-IN) PROVV. ASSICURATIVE - da Broker Captive');
                $table->decimal('payout_rete_credito', 15, 2)->default(0.00)->comment('AMMONTARE PROVVIGIONI RETE - INTERMEDIAZIONE CREDITO');
                $table->decimal('payout_rete_ass_banche', 15, 2)->default(0.00)->comment('(PAY-OUT) PROVV. RETE ASSICURATIVA - da banche');
                $table->decimal('payout_rete_ass_broker', 15, 2)->default(0.00)->comment('(PAY-OUT) PROVV. RETE ASSICURATIVA - da Broker');
                $table->decimal('payout_rete_ass_broker_cap', 15, 2)->default(0.00)->comment('(PAY-OUT) PROVV. RETE ASSICURATIVA - da Broker Captive');
                $table->integer('num_rivalse')->default(0)->comment('N° RIVALSE AI SENSI DELL ART. 125 - SEXIES, DEL TUB');
                $table->decimal('importo_retrocesse', 15, 2)->default(0.00)->comment('AMMONTARE DELLE PROVVIGIONI RETROCESSE AL FINANZIATORE');
                $table->boolean('is_convenzione')->default(true)->comment('Convenzione SI/NO');
                $table->timestamp('created_at')->nullable()->comment('Data e ora di creazione del record');
                $table->timestamp('updated_at')->nullable()->comment('Data e ora dell\'ultima modifica del record');
                $table->index(['period'], 'oam_semestrales_period_index');
                $table->comment('Dati aggregati per il report OAM: Profilo Economico/Operativo Base');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('oam_semestrales');
        Schema::dropIfExists('oam_pratiches');
        Schema::dropIfExists('blacklist_clienti_fornitori');
        Schema::dropIfExists('client_relations');
        Schema::dropIfExists('client_mandates');
        Schema::dropIfExists('training_records');
        Schema::dropIfExists('suspicious_activity_reports');
        Schema::dropIfExists('audit_checklist_evaluations');
        Schema::dropIfExists('audit_findings');
        Schema::dropIfExists('audits');
        Schema::dropIfExists('remediations');
        Schema::dropIfExists('quality_reviews');
    }
};
