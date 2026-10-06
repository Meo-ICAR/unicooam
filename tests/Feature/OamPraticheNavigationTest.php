<?php

namespace Tests\Feature;

use App\Filament\Resources\OamPratiches\OamPraticheResource;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class OamPraticheNavigationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_oam_dettaglio_is_not_registered_in_the_navigation(): void
    {
        $this->actingAs(User::factory()->create());

        $this->assertFalse(OamPraticheResource::shouldRegisterNavigation());
        $this->assertNotContains('OAM Dettaglio', collect(filament()->getNavigation())
            ->flatMap(fn ($group) => $group->getItems())
            ->map(fn ($item) => $item->getLabel())
            ->all());
    }

    public function test_oam_dettaglio_resource_stays_reachable_by_url(): void
    {
        $this->assertNotEmpty(OamPraticheResource::getUrl('index'));
    }
}
