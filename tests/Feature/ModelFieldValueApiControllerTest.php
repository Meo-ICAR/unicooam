<?php

namespace Tests\Feature;

use App\Models\PROFORMA\Fornitore;
use Tests\TestCase;

class ModelFieldValueApiControllerTest extends TestCase
{
    public function test_show_returns_404_for_an_unknown_model(): void
    {
        $response = $this->getJson('/api/models/unknown-model/'.fake()->uuid());

        $response->assertNotFound();
    }

    public function test_show_returns_404_when_the_record_does_not_exist(): void
    {
        $response = $this->getJson('/api/models/fornitore/'.fake()->uuid());

        $response->assertNotFound();
        $response->assertJson(['message' => 'Record non trovato.']);
    }

    public function test_show_returns_the_record_fields(): void
    {
        $fornitore = Fornitore::query()->first();

        if (! $fornitore) {
            $this->markTestSkipped('Nessun fornitore disponibile sul database proforma per il test.');
        }

        $response = $this->getJson('/api/models/fornitore/'.$fornitore->id);

        $response->assertOk();
        $response->assertJson([
            'model' => 'fornitore',
            'id' => $fornitore->id,
        ]);
        $response->assertJsonPath('fields.name', $fornitore->name);
    }

    public function test_rejects_a_field_that_does_not_exist_on_the_model(): void
    {
        $response = $this->patchJson('/api/models/fornitore/'.fake()->uuid(), [
            'field' => 'campo_inesistente',
            'value' => 'x',
        ]);

        $response->assertStatus(422);
    }

    public function test_returns_404_for_an_unknown_model(): void
    {
        $response = $this->patchJson('/api/models/unknown-model/'.fake()->uuid(), [
            'field' => 'name',
            'value' => 'x',
        ]);

        $response->assertNotFound();
    }

    public function test_returns_404_when_the_record_does_not_exist(): void
    {
        $response = $this->patchJson('/api/models/fornitore/'.fake()->uuid(), [
            'field' => 'description',
            'value' => 'x',
        ]);

        $response->assertNotFound();
        $response->assertJson(['message' => 'Record non trovato.']);
    }
}
