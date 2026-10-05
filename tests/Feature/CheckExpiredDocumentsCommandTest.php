<?php

namespace Tests\Feature;

use App\Console\Commands\CheckExpiredDocumentsCommand;
use App\Enums\Severity;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentSchedule;
use App\Models\DocumentType;
use App\Models\Employee;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Tests\TestCase;

class CheckExpiredDocumentsCommandTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function schedule(int $daysOverdue, string $name = 'Doc'): void
    {
        $company = Company::factory()->create();
        $employee = Employee::query()->create(['company_id' => $company->id, 'name' => 'Mario Rossi', 'email' => 'm@example.com']);
        $type = DocumentType::query()->firstOrCreate(['slug' => 'patente-expired'], ['name' => 'Patente', 'priority' => 1]);

        $document = Document::query()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'documentable_type' => Employee::class,
            'documentable_id' => (string) $employee->id,
            'document_type_id' => $type->id,
            'name' => $name,
            'status' => 'approvato',
            'is_monitored' => true,
            'expires_at' => now()->subDays($daysOverdue)->toDateString(),
        ]);

        DocumentSchedule::query()->create([
            'document_id' => $document->id,
            'documentable_group_key' => 'employee|'.$employee->id,
            'document_name' => $name,
            'document_type_name' => 'Patente',
            'entity_name' => 'Mario Rossi',
            'documentable_type' => 'employee',
            'documentable_id' => (string) $employee->id,
            'expires_at' => now()->subDays($daysOverdue)->toDateString(),
            'days_until_expiry' => -$daysOverdue,
            'status' => 'approvato',
        ]);
    }

    private function checkResult(): array
    {
        $command = $this->app->make(CheckExpiredDocumentsCommand::class);
        $command->setLaravel($this->app);
        $command->run(new ArrayInput(['--no-sync' => true]), new NullOutput);

        return $command->checkStatus()->toArray();
    }

    public function test_severity_follows_the_oldest_overdue_document(): void
    {
        $this->assertSame('ok', $this->checkResult()['severity']);

        $this->schedule(5);
        $this->assertSame('ok', $this->checkResult()['severity']);

        $this->schedule(8);
        $this->assertSame('regular', $this->checkResult()['severity']);

        $this->schedule(16);
        $this->assertSame('warning', $this->checkResult()['severity']);

        $this->schedule(31);
        $this->assertSame('alert', $this->checkResult()['severity']);
    }

    public function test_thresholds_are_strictly_greater_than(): void
    {
        $this->schedule(7);
        $this->assertSame(Severity::Ok->value, $this->checkResult()['severity']);
    }

    public function test_value_is_the_list_of_expired_documents_oldest_first(): void
    {
        $this->schedule(10, 'Visura');
        $this->schedule(40, 'Casellario');
        $this->schedule(-3, 'Futuro');

        $status = $this->checkResult();

        $this->assertStringContainsString('Casellario', $status['value']);
        $this->assertStringContainsString('Visura', $status['value']);
        $this->assertStringNotContainsString('Futuro', $status['value']);
        $this->assertLessThan(strpos($status['value'], 'Visura'), strpos($status['value'], 'Casellario'));
        $this->assertSame('2 documenti scaduti, il più vecchio da 40 giorni.', $status['details']);
        $this->assertSame('alert', $status['severity']);
    }

    public function test_is_exposed_through_the_checks_api(): void
    {
        $this->schedule(20);

        $this->getJson('/api/checks')->assertJsonStructure(['checks' => ['documents:check-expired']]);
    }
}
