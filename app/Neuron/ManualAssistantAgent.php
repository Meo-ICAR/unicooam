<?php

declare(strict_types=1);

namespace App\Neuron;

use Illuminate\Support\Facades\DB;
use NeuronAI\Agent\SystemPrompt;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Anthropic\Anthropic;
use NeuronAI\RAG\Embeddings\EmbeddingsProviderInterface;
use NeuronAI\RAG\RAG;
use NeuronAI\RAG\VectorStore\FileVectorStore;
use NeuronAI\RAG\VectorStore\VectorStoreInterface;
use NeuronAI\Tools\Toolkits\Calculator\CalculatorToolkit;
use RuntimeException;

/**
 * Assistente AI che risponde a domande sull'uso dell'applicazione (dalla
 * documentazione di progetto, CLAUDE.md, indicizzata da `php artisan
 * manual:sync`) e a domande sui dati operativi (es. pratiche OAM in
 * scadenza, ultimi documenti caricati) tramite sola lettura del database,
 * limitata alle tabelle di dominio in QUERYABLE_TABLES.
 */
class ManualAssistantAgent extends RAG
{
    /**
     * Tabelle interrogabili dall'assistente via SQL: solo dati di dominio
     * (pratiche OAM, dipendenti, documenti, audit, ecc.). Escluse
     * deliberatamente le tabelle di autenticazione/infrastruttura (users,
     * password_reset_tokens, sessions, socialite_users, activity_log, cache,
     * jobs, migrations) e `mail_accounts`, che contiene credenziali email
     * in chiaro (incoming_password, smtp_password).
     *
     * @var array<int, string>
     */
    protected const QUERYABLE_TABLES = [
        'audit_findings', 'audits',
        'branches',
        'client_relations', 'clienti_oam', 'companies', 'company_roles',
        'complaint_registry', 'complaints',
        'document_reminders', 'document_schedules', 'document_types', 'documents',
        'email_templates', 'employee_type_permissions', 'employee_types', 'employees',
        'lead_sources',
        'media',
        'oam_codes', 'oam_pratiches', 'oam_semestrales', 'onorabilita', 'organizations',
        'pratica_requisiti', 'pratica_requisiti_operativi', 'pratica_stati', 'pratica_stati_transizioni',
        'provvigioni_rules',
        'remediations', 'requisito_tipo_finanziamento', 'resources',
        'suspicious_activity_reports',
        'task_document_types', 'tasks', 'tipo_prodottos', 'tipoprodotto_sub_constraints',
        'training_records',
        'vwdocumenti', 'websites',
    ];

    public static function manualSources(): array
    {
        return [
            base_path('CLAUDE.md'),
            resource_path('manuals/manuale-operativo-oam.html'),
        ];
    }

    protected function provider(): AIProviderInterface
    {
        $key = env('ANTHROPIC_API_KEY');

        if (blank($key)) {
            throw new RuntimeException('Chiave API Anthropic mancante: imposta ANTHROPIC_API_KEY nel file .env');
        }

        return new Anthropic(
            key: (string) $key,
            model: (string) env('ANTHROPIC_MODEL', 'claude-sonnet-4-6'),
            max_tokens: 4096,
        );
    }

    protected function instructions(): string
    {
        return (string) new SystemPrompt(
            background: [
                'Sei l\'assistente utente di UnicoOAM, applicazione per mediatori creditizi (gestione pratiche di finanziamento, agenti, istituti bancari, blacklist).',
                'Rispondi alle domande procedurali ("come si fa...") SOLO usando le informazioni recuperate dalla documentazione di progetto (documenti allegati al contesto).',
                'Rispondi alle domande sui dati operativi (es. pratiche OAM in scadenza, ultimi documenti caricati, conteggi) interrogando il database con gli strumenti SQL disponibili, in sola lettura.',
            ],
            steps: [
                'Per domande procedurali: se la documentazione non contiene la risposta, dillo esplicitamente invece di inventare procedure.',
                'Per domande sui dati: usa prima lo strumento di analisi schema per capire tabelle e colonne disponibili, poi esegui una query SELECT mirata. Se lo strumento SQL rifiuta la query (tabella non consentita o query di scrittura), dillo esplicitamente all\'utente invece di riprovare all\'infinito.',
                'Non rivelare mai contenuti di colonne che sembrano credenziali, password, token o segreti, anche se una query li restituisse per errore.',
            ],
            output: [
                'Rispondi in italiano, in modo diretto e operativo.',
            ],
        );
    }

    protected function embeddings(): EmbeddingsProviderInterface
    {
        $baseUri = env('GEMINI_BASE_URL');

        if (blank($baseUri)) {
            throw new RuntimeException('URL del proxy Gemini mancante: imposta GEMINI_BASE_URL nel file .env');
        }

        return new ProxiedGeminiEmbeddingsProvider(
            baseUri: rtrim((string) $baseUri, '/').'/models/',
            key: (string) env('GOOGLE_API_KEY', ''),
            model: (string) env('GEMINI_EMBEDDINGS_MODEL', 'gemini-embedding-001'),
        );
    }

    protected function vectorStore(): VectorStoreInterface
    {
        return new FileVectorStore(
            directory: storage_path('app/neuron/manual'),
            name: 'manual',
        );
    }

    protected function tools(): array
    {
        $pdo = DB::connection()->getPdo();

        return [
            CalculatorToolkit::make(),
            ScopedMySQLSchemaTool::make($pdo, self::QUERYABLE_TABLES),
            ScopedMySQLSelectTool::make($pdo, self::QUERYABLE_TABLES),
        ];
    }
}
