<?php

namespace Tests\Feature;

use App\Filament\Resources\DocumentTypes\DocumentTypeResource;
use App\Filament\Resources\DocumentTypes\Pages\ListDocumentTypes;
use App\Filament\Resources\EmailTemplates\EmailTemplateResource;
use App\Filament\Resources\Tasks\TaskResource;
use App\Models\DocumentType;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManagedInUnicobpmTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_document_types_tasks_and_email_templates_are_only_viewed_here_and_edited_in_unicobpm(): void
    {
        config(['services.bpm.url' => 'https://unicobpm.test/']);
        Filament::setCurrentPanel('admin');
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $type = DocumentType::query()->create(['name' => 'Carta d\'identità', 'code' => 'CI-'.uniqid()]);

        foreach ([DocumentTypeResource::class, TaskResource::class, EmailTemplateResource::class] as $resource) {
            $this->assertFalse($resource::canCreate(), $resource);
            $this->assertFalse($resource::canDeleteAny(), $resource);
        }

        $this->assertFalse(DocumentTypeResource::canEdit($type));
        $this->assertSame('https://unicobpm.test/admin/tasks', TaskResource::unicobpmUrl());

        Livewire::test(ListDocumentTypes::class)
            ->assertActionHasUrl('gestisciInUnicobpm', 'https://unicobpm.test/admin/document-types')
            ->assertTableActionHasUrl('apriInUnicobpm', 'https://unicobpm.test/admin/document-types/'.$type->getKey().'/edit', $type);
    }
}
