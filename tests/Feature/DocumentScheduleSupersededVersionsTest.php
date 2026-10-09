<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Employee;
use App\Services\DocumentReminderService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DocumentScheduleSupersededVersionsTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Company $company;

    private Employee $employee;

    private DocumentType $type;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->employee = $this->employee('Mario Rossi');
        $this->type = $this->type();
    }

    private function employee(string $name): Employee
    {
        return Employee::query()->create(['company_id' => $this->company->id, 'name' => $name, 'email' => Str::random(6).'@example.com']);
    }

    private function type(): DocumentType
    {
        return DocumentType::query()->create(['name' => 'Tipo '.Str::random(6), 'slug' => 'tipo-'.Str::random(6), 'priority' => 1]);
    }

    /** @param array<string, mixed> $attributes */
    private function document(string $emittedAt, string $expiresAt, array $attributes = []): Document
    {
        // Senza eventi: il listener "saving" ricalcola expires_at dalla durata del tipo.
        return Document::withoutEvents(fn () => Document::query()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'documentable_type' => 'employee',
            'documentable_id' => (string) $this->employee->id,
            'document_type_id' => $this->type->id,
            'name' => 'Casellario',
            'status' => 'approvato',
            'is_monitored' => true,
            'emitted_at' => $emittedAt,
            'expires_at' => $expiresAt,
            ...$attributes,
        ]));
    }

    /** @return list<string> */
    private function scheduledIds(): array
    {
        return app(DocumentReminderService::class)->scheduleQuery()->pluck('documents.id')->all();
    }

    public function test_older_version_of_the_same_type_is_not_scheduled(): void
    {
        $old = $this->document(now()->subYears(2)->toDateString(), now()->subYear()->toDateString());
        $new = $this->document(now()->subDays(20)->toDateString(), now()->addDays(30)->toDateString());

        $this->assertSame([$new->id], $this->scheduledIds());
        $this->assertNotContains($old->id, $this->scheduledIds());
    }

    public function test_a_document_without_newer_version_stays_scheduled_even_when_overdue(): void
    {
        $overdue = $this->document(now()->subYears(2)->toDateString(), now()->subDays(10)->toDateString());

        $this->assertSame([$overdue->id], $this->scheduledIds());
    }

    public function test_renewed_versions_are_excluded(): void
    {
        $renewed = $this->document(now()->subYear()->toDateString(), now()->addDays(10)->toDateString(), [
            'metadata' => ['renewed_to_uuid' => (string) Str::uuid()],
        ]);
        $expired = $this->document(now()->subYear()->toDateString(), now()->addDays(10)->toDateString(), ['status' => 'expired']);

        $this->assertNotContains($renewed->id, $this->scheduledIds());
        $this->assertNotContains($expired->id, $this->scheduledIds());
    }

    public function test_a_newer_rejected_version_does_not_supersede_the_previous_one(): void
    {
        $valid = $this->document(now()->subYear()->toDateString(), now()->addDays(10)->toDateString());
        $this->document(now()->subDays(5)->toDateString(), now()->addYear()->toDateString(), ['status' => 'respinto']);

        $this->assertSame([$valid->id], $this->scheduledIds());
    }

    public function test_a_newer_soft_deleted_version_does_not_supersede_the_previous_one(): void
    {
        $valid = $this->document(now()->subYear()->toDateString(), now()->addDays(10)->toDateString());
        $this->document(now()->subDays(5)->toDateString(), now()->addYear()->toDateString())->delete();

        $this->assertSame([$valid->id], $this->scheduledIds());
    }

    public function test_other_types_and_other_recipients_are_not_affected(): void
    {
        $a = $this->document(now()->subYear()->toDateString(), now()->addDays(10)->toDateString());
        $otherType = $this->document(now()->subDays(5)->toDateString(), now()->addDays(20)->toDateString(), ['document_type_id' => $this->type()->id]);
        $this->employee = $this->employee('Luca Bianchi');
        $otherEmployee = $this->document(now()->subDays(5)->toDateString(), now()->addDays(20)->toDateString(), ['document_type_id' => $a->document_type_id]);

        $this->assertEqualsCanonicalizing([$a->id, $otherType->id, $otherEmployee->id], $this->scheduledIds());
    }

    public function test_on_equal_emission_date_the_later_expiry_wins(): void
    {
        $emitted = now()->subMonths(3)->toDateString();
        $earlier = $this->document($emitted, now()->addDays(10)->toDateString());
        $later = $this->document($emitted, now()->addDays(40)->toDateString());

        $this->assertSame([$later->id], $this->scheduledIds());
        $this->assertNotContains($earlier->id, $this->scheduledIds());
    }

    public function test_identical_emission_and_expiry_dates_do_not_hide_each_other(): void
    {
        $emitted = now()->subMonths(3)->toDateString();
        $expires = now()->addDays(10)->toDateString();
        $a = $this->document($emitted, $expires);
        $b = $this->document($emitted, $expires);

        $this->assertEqualsCanonicalizing([$a->id, $b->id], $this->scheduledIds());
    }

    public function test_a_document_renewed_by_another_type_is_excluded_once_the_renewing_type_is_newer(): void
    {
        $renewingType = $this->type();
        $this->type->update(['renewed_by_id' => $renewingType->id]);

        $old = $this->document(now()->subYears(2)->toDateString(), now()->subYear()->toDateString());
        $renewing = $this->document(now()->subDays(20)->toDateString(), now()->addDays(30)->toDateString(), ['document_type_id' => $renewingType->id]);

        $this->assertSame([$renewing->id], $this->scheduledIds());
        $this->assertNotContains($old->id, $this->scheduledIds());
    }

    public function test_a_document_is_not_excluded_when_the_renewing_type_is_older_or_invalid_or_for_another_recipient(): void
    {
        $renewingType = $this->type();
        $this->type->update(['renewed_by_id' => $renewingType->id]);

        $current = $this->document(now()->subDays(20)->toDateString(), now()->addDays(30)->toDateString());
        $olderRenewing = $this->document(now()->subYear()->toDateString(), now()->addDays(5)->toDateString(), ['document_type_id' => $renewingType->id]);
        $rejectedRenewing = $this->document(now()->subDays(2)->toDateString(), now()->addDays(60)->toDateString(), ['document_type_id' => $renewingType->id, 'status' => 'respinto']);
        $this->employee = $this->employee('Luca Bianchi');
        $otherRecipient = $this->document(now()->subDays(1)->toDateString(), now()->addDays(60)->toDateString(), ['document_type_id' => $renewingType->id]);

        $this->assertContains($current->id, $this->scheduledIds());
        $this->assertContains($olderRenewing->id, $this->scheduledIds());
        $this->assertContains($otherRecipient->id, $this->scheduledIds());
        $this->assertNotContains($rejectedRenewing->id, $this->scheduledIds());
    }
}
