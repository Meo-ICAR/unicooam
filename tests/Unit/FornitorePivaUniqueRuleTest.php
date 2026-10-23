<?php

namespace Tests\Unit;

use App\Models\Fornitore;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Tests\TestCase;

/**
 * Verifica la regola di unicità della partita IVA usata dal form Fornitore
 * (App\Filament\Resources\Fornitores\Schemas\FornitoreForm). Ogni test crea i
 * fornitori che gli servono: non dipende dai dati del database condiviso.
 */
class FornitorePivaUniqueRuleTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @var array<int, string>
     */
    protected array $connectionsToTransact = [null, 'core'];

    private function pivaUniqueRule(): Unique
    {
        return Rule::unique(Fornitore::class, 'piva')
            ->where(fn ($query) => $query->whereNotIn('piva', ['', '---']));
    }

    public function test_placeholder_piva_values_do_not_conflict_with_each_other(): void
    {
        $record = Fornitore::create(['name' => 'Segnaposto 1', 'piva' => '---']);
        Fornitore::create(['name' => 'Segnaposto 2', 'piva' => '---']);

        $validator = Validator::make(
            ['piva' => '---'],
            ['piva' => [$this->pivaUniqueRule()->ignore($record->id)]]
        );

        $this->assertFalse($validator->fails());
    }

    public function test_blank_piva_values_do_not_conflict_with_each_other(): void
    {
        $record = Fornitore::create(['name' => 'Senza piva 1', 'piva' => '']);
        Fornitore::create(['name' => 'Senza piva 2', 'piva' => null]);

        $validator = Validator::make(
            ['piva' => ''],
            ['piva' => [$this->pivaUniqueRule()->ignore($record->id)]]
        );

        $this->assertFalse($validator->fails());
    }

    public function test_a_genuinely_duplicated_piva_still_fails_validation(): void
    {
        $firstRecord = Fornitore::create(['name' => 'Doppione 1', 'piva' => 'IT-DOPPIA-123']);
        Fornitore::create(['name' => 'Doppione 2', 'piva' => 'IT-DOPPIA-123']);

        $validator = Validator::make(
            ['piva' => 'IT-DOPPIA-123'],
            ['piva' => [$this->pivaUniqueRule()->ignore($firstRecord->id)]]
        );

        $this->assertTrue($validator->fails());
    }

    public function test_a_new_unique_piva_passes_validation(): void
    {
        $validator = Validator::make(
            ['piva' => 'IT-TEST-PIVA-UNIVOCA-'.uniqid()],
            ['piva' => [$this->pivaUniqueRule()]]
        );

        $this->assertFalse($validator->fails());
    }
}
