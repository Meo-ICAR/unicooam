<?php

namespace Tests\Feature;

use App\Models\Fornitore;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;
use Unico\Core\Models\Company;

class ProducerLookupApiControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['unico-core.bridge.api_key' => 'chiave-di-prova']);
    }

    private function lookup(string $tel, ?string $key = 'chiave-di-prova')
    {
        return $this->getJson('/api/produttori/lookup?tel='.urlencode($tel), $key === null ? [] : ['X-Api-Key' => $key]);
    }

    public function test_the_producer_is_found_by_phone_in_any_format_and_the_response_has_company_and_codes(): void
    {
        $societa = Company::query()->create(['name' => 'Races Prova', 'vat_number' => 'IT 058-22361007']);
        Fornitore::query()->create(['name' => 'Rossi Mario Srl', 'nome' => 'Mario Rossi', 'tel' => '+39 392 796 8199', 'email' => 'mario@example.test', 'cf' => 'RSSMRA80A01H501U', 'piva' => '12345678903', 'sigla' => 'MR', 'codice' => 'SZ0001', 'company_id' => $societa->getKey(), 'is_active' => true]);

        foreach (['393927968199', '3927968199', '+39 3927968199'] as $tel) {
            $this->lookup($tel)->assertOk()->assertJson([
                'trovato' => true,
                'produttore' => ['denominazione' => 'Rossi Mario Srl', 'referente' => 'Mario Rossi', 'sigla' => 'MR', 'codice' => 'SZ0001', 'societa_partita_iva' => '05822361007', 'attivo' => true],
            ]);
        }
    }

    public function test_an_unknown_or_too_short_number_is_not_found_and_an_inactive_producer_is_reported_as_such(): void
    {
        Fornitore::query()->create(['name' => 'Ex produttore', 'tel' => '3331112222', 'is_active' => false]);

        $this->lookup('3999999999')->assertOk()->assertExactJson(['trovato' => false]);
        $this->lookup('123')->assertOk()->assertExactJson(['trovato' => false]);
        $this->lookup('393331112222')->assertOk()->assertJson(['trovato' => true, 'produttore' => ['attivo' => false]]);
        $this->getJson('/api/produttori/lookup', ['X-Api-Key' => 'chiave-di-prova'])->assertStatus(422);
    }

    public function test_without_the_right_key_nothing_is_returned(): void
    {
        Fornitore::query()->create(['name' => 'Rossi', 'tel' => '3927968199', 'is_active' => true]);

        $this->lookup('3927968199', null)->assertUnauthorized();
        $this->lookup('3927968199', 'sbagliata')->assertUnauthorized();

        config(['unico-core.bridge.api_key' => '', 'services.bpm.api_key' => '']);
        $this->lookup('3927968199')->assertStatus(503);
    }
}
