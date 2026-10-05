<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Filament\Resources\DocumentSchedules\DocumentScheduleResource;
use App\Models\Document;
use App\Models\DocumentSchedule;
use App\Models\Employee;
use App\Models\PROFORMA\Fornitore;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DocumentScheduleExportFilterTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function makeSchedule(string $type, ?string $id, string $name): DocumentSchedule
    {
        return DocumentSchedule::create([
            'document_id' => Document::create(['name' => $name, 'documentable_type' => $type, 'documentable_id' => $id])->id,
            'documentable_group_key' => "{$type}|{$id}",
            'document_name' => $name,
            'document_type_name' => 'Tipo',
            'entity_name' => $name,
            'documentable_type' => $type,
            'documentable_id' => $id,
            'days_until_expiry' => 10,
            'status' => 'valid',
            'reminders_count' => 0,
        ]);
    }

    public function test_export_excludes_inactive_employees_and_suppliers(): void
    {
        $active = Employee::create(['name' => 'Attivo']);
        $terminated = Employee::create(['name' => 'Cessato', 'termination_date' => now()->subDay()]);
        $deleted = Employee::create(['name' => 'Eliminato']);
        $deleted->delete();

        $morph = $active->getMorphClass();
        $this->makeSchedule($morph, (string) $active->id, 'attivo');
        $this->makeSchedule($morph, (string) $terminated->id, 'cessato');
        $this->makeSchedule($morph, (string) $deleted->id, 'eliminato');
        $activeSupplier = Fornitore::create(['name' => 'Fornitore attivo']);
        $dismissedSupplier = Fornitore::create(['name' => 'Fornitore dismesso', 'dismissed_at' => now()->subDay()]);
        $deletedSupplier = Fornitore::create(['name' => 'Fornitore eliminato']);
        $deletedSupplier->delete();

        $supplierMorph = $activeSupplier->getMorphClass();
        $this->makeSchedule($supplierMorph, $activeSupplier->id, 'fornitore attivo');
        $this->makeSchedule($supplierMorph, $dismissedSupplier->id, 'fornitore dismesso');
        $this->makeSchedule($supplierMorph, $deletedSupplier->id, 'fornitore eliminato');
        $this->makeSchedule('other_type', '1', 'altro');

        $names = DocumentScheduleResource::excludeInactiveEntities(DocumentSchedule::query())
            ->pluck('document_name')
            ->sort()
            ->values()
            ->all();

        $this->assertSame(['altro', 'attivo', 'fornitore attivo'], $names);
    }

    public function test_sync_skips_documents_of_inactive_employees_and_suppliers(): void
    {
        $active = Employee::create(['name' => 'Attivo']);
        $terminated = Employee::create(['name' => 'Cessato', 'termination_date' => now()->subDay()]);
        $dismissedSupplier = Fornitore::create(['name' => 'Dismesso', 'dismissed_at' => now()->subDay()]);

        foreach ([[$active->getMorphClass(), $active->id, 'attivo'], [$terminated->getMorphClass(), $terminated->id, 'cessato'], [$dismissedSupplier->getMorphClass(), $dismissedSupplier->id, 'dismesso']] as [$type, $id, $name]) {
            Document::create([
                'name' => $name,
                'documentable_type' => $type,
                'documentable_id' => $id,
                'status' => DocumentStatus::PENDING->value,
            ]);
        }

        DocumentScheduleResource::syncScheduleTable();

        $this->assertSame(['attivo'], DocumentSchedule::pluck('document_name')->all());
    }
}
