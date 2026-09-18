<?php

namespace Tests\Feature;

use App\Filament\Resources\Companies\Pages\EditCompany;
use App\Filament\Resources\Companies\RelationManagers\ChatUsageRelationManager;
use App\Models\ChatMessage;
use App\Models\Company;
use App\Models\User;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChatUsageRelationManagerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_relation_manager_renders_monthly_totals_for_the_company(): void
    {
        $this->actingAs(User::factory()->create());

        $company = Company::factory()->create();
        $user = User::factory()->create();

        $this->createAssistantMessage($company, $user, '2026-09-05', inputTokens: 100, outputTokens: 50);

        $rows = $company->chatMessages()
            ->withoutGlobalScope('owned')
            ->orWhereNull('company_id')
            ->selectRaw("MIN(id) AS id, DATE_FORMAT(created_at, '%Y-%m') AS period")
            ->groupBy('period')
            ->get();

        Livewire::test(ChatUsageRelationManager::class, [
            'ownerRecord' => $company,
            'pageClass' => EditCompany::class,
        ])
            ->assertOk()
            ->assertCanSeeTableRecords($rows)
            ->callTableAction('dettaglioUtenti', $rows->first())
            ->assertOk();
    }

    public function test_monthly_totals_group_tokens_by_month_and_include_unassigned_messages(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = User::factory()->create();

        // Settembre: un messaggio esplicitamente della company, uno senza company_id (da includere comunque).
        $this->createAssistantMessage($company, $user, '2026-09-05', inputTokens: 100, outputTokens: 50);
        $this->createAssistantMessage(null, $user, '2026-09-10', inputTokens: 20, outputTokens: 5);

        // Agosto: stesso company, mese diverso.
        $this->createAssistantMessage($company, $user, '2026-08-20', inputTokens: 10, outputTokens: 2);

        // Messaggio di un'altra company: non deve comparire nel riepilogo.
        $this->createAssistantMessage($otherCompany, $user, '2026-09-12', inputTokens: 999, outputTokens: 999);

        // Messaggio utente senza usage: conta nei messaggi ma non nei token.
        ChatMessage::query()->forceCreate([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'thread_id' => 'thread-user',
            'role' => 'user',
            'content' => [['type' => 'text', 'content' => 'ciao']],
            'meta' => null,
            'created_at' => '2026-09-06 10:00:00',
        ]);

        $rows = $company->chatMessages()
            ->withoutGlobalScope('owned')
            ->orWhereNull('company_id')
            ->selectRaw("
                MIN(id) AS id,
                DATE_FORMAT(created_at, '%Y-%m') AS period,
                COUNT(*) AS messages_count,
                SUM(CAST(meta->>'$.usage.input_tokens' AS UNSIGNED)) AS input_tokens,
                SUM(CAST(meta->>'$.usage.output_tokens' AS UNSIGNED)) AS output_tokens,
                SUM(CAST(meta->>'$.usage.input_tokens' AS UNSIGNED)) + SUM(CAST(meta->>'$.usage.output_tokens' AS UNSIGNED)) AS total_tokens
            ")
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->keyBy('period');

        $this->assertCount(2, $rows);

        $august = $rows->get('2026-08');
        $this->assertNotNull($august);
        $this->assertSame(1, (int) $august->messages_count);
        $this->assertSame(12, (int) $august->total_tokens);

        $september = $rows->get('2026-09');
        $this->assertNotNull($september);
        $this->assertSame(3, (int) $september->messages_count);
        $this->assertSame(175, (int) $september->total_tokens);
    }

    public function test_users_breakdown_for_returns_per_user_token_totals_for_the_given_month(): void
    {
        $company = Company::factory()->create();
        $userA = User::factory()->create(['name' => 'Utente A']);
        $userB = User::factory()->create(['name' => 'Utente B']);

        $this->createAssistantMessage($company, $userA, '2026-09-05', inputTokens: 100, outputTokens: 50);
        $this->createAssistantMessage($company, $userA, '2026-09-06', inputTokens: 10, outputTokens: 5);
        $this->createAssistantMessage($company, $userB, '2026-09-07', inputTokens: 30, outputTokens: 20);
        // Fuori periodo: non deve comparire.
        $this->createAssistantMessage($company, $userA, '2026-08-01', inputTokens: 1000, outputTokens: 1000);

        $relationManager = new ChatUsageRelationManager;
        $ownerRecordProperty = new \ReflectionProperty(RelationManager::class, 'ownerRecord');
        $ownerRecordProperty->setAccessible(true);
        $ownerRecordProperty->setValue($relationManager, $company);

        $method = new \ReflectionMethod($relationManager, 'usersBreakdownFor');
        $method->setAccessible(true);

        $breakdown = collect($method->invoke($relationManager, '2026-09'))->keyBy('utente');

        $this->assertCount(2, $breakdown);
        $this->assertSame(165, $breakdown->get('Utente A')['token_totali']);
        $this->assertSame(2, $breakdown->get('Utente A')['messaggi']);
        $this->assertSame(50, $breakdown->get('Utente B')['token_totali']);
    }

    private function createAssistantMessage(?Company $company, User $user, string $date, int $inputTokens, int $outputTokens): ChatMessage
    {
        return ChatMessage::query()->forceCreate([
            'company_id' => $company?->id,
            'user_id' => $user->id,
            'thread_id' => 'thread-'.uniqid(),
            'role' => 'assistant',
            'content' => [['type' => 'text', 'content' => 'risposta']],
            'meta' => [
                'usage' => [
                    'input_tokens' => $inputTokens,
                    'output_tokens' => $outputTokens,
                    'reasoning_tokens' => 0,
                    'cached_input_tokens' => 0,
                ],
            ],
            'created_at' => $date.' 10:00:00',
        ]);
    }
}
