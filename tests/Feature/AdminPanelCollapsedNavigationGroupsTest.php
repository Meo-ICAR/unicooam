<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminPanelCollapsedNavigationGroupsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_dashboard_resyncs_collapsed_navigation_groups_from_localstorage_once(): void
    {
        Company::factory()->create();
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('filament.admin.pages.dashboard'));

        $response->assertOk();
        $response->assertSee("localStorage.getItem('collapsedGroupsResyncedV1')", false);
        $response->assertSee("localStorage.removeItem('collapsedGroups')", false);
    }
}
