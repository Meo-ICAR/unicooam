<?php

namespace Tests\Feature;

use App\Enums\Severity;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Employee;
use App\Services\DocumentReminderService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckStatusApiControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function documentExpiringIn(int $days, string $status = 'approvato', bool $monitored = true): Document
    {
        $company = Company::factory()->create();
        $employee = Employee::query()->create(['company_id' => $company->id, 'name' => 'Mario Rossi', 'email' => 'm@example.com']);
        $type = DocumentType::query()->firstOrCreate(['slug' => 'patente-check'], ['name' => 'Patente', 'priority' => 1]);

        return Document::query()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'documentable_type' => Employee::class,
            'documentable_id' => (string) $employee->id,
            'document_type_id' => $type->id,
            'name' => "Patente {$days}",
            'status' => $status,
            'is_monitored' => $monitored,
            'expires_at' => now()->addDays($days)->toDateString(),
        ]);
    }

    public function test_status_is_ok_without_expiring_documents(): void
    {
        $this->documentExpiringIn(90);

        $status = app(DocumentReminderService::class)->expiryStatus();

        $this->assertSame(0, $status->value);
        $this->assertSame(Severity::Ok, $status->severity);
    }

    public function test_severity_follows_the_most_urgent_document(): void
    {
        $this->documentExpiringIn(25);
        $this->assertSame(Severity::Regular, app(DocumentReminderService::class)->expiryStatus()->severity);

        $this->documentExpiringIn(5);
        $this->assertSame(Severity::Warning, app(DocumentReminderService::class)->expiryStatus()->severity);

        $this->documentExpiringIn(-3);
        $status = app(DocumentReminderService::class)->expiryStatus();

        $this->assertSame(Severity::Alert, $status->severity);
        $this->assertSame(3, $status->value);
        $this->assertStringContainsString('SCADUTO', $status->details);
    }

    public function test_ignores_unmonitored_and_rejected_documents(): void
    {
        $this->documentExpiringIn(-3, monitored: false);
        $this->documentExpiringIn(-3, status: 'respinto');

        $this->assertSame(0, app(DocumentReminderService::class)->expiryStatus()->value);
    }

    public function test_api_returns_value_and_severity(): void
    {
        $this->documentExpiringIn(5);

        $this->getJson('/api/checks/documents:check-expiring')
            ->assertOk()
            ->assertJsonPath('command', 'documents:check-expiring')
            ->assertJsonPath('value', 1)
            ->assertJsonPath('severity', 'warning');
    }

    public function test_api_rejects_commands_that_are_not_checks(): void
    {
        $this->getJson('/api/checks/migrate')->assertNotFound();
    }
}
