<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class ManualeOperativoRouteTest extends TestCase
{
    public function test_manual_route_requires_authentication(): void
    {
        $this->get(route('manuale-operativo-oam'))->assertRedirect();
    }

    public function test_authenticated_user_can_download_the_manual(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('manuale-operativo-oam'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/html; charset=UTF-8');
    }
}
