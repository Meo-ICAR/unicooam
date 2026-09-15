<?php

namespace Tests\Unit;

use App\Models\PROFORMA\Fornitore;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Tests\TestCase;

/**
 * Verifica la regola di unicità della partita IVA usata dal form Fornitore
 * (App\Filament\Resources\Fornitores\Schemas\FornitoreForm). Le assertion
 * leggono record reali dal database proforma (sola lettura, nessuna
 * scrittura) perché quella connessione non ha migrazioni proprie in questo
 * repo e non va toccata nei test.
 */
class FornitorePivaUniqueRuleTest extends TestCase
{
    private function pivaUniqueRule(): Unique
    {
        return Rule::unique(Fornitore::class, 'piva')
            ->where(fn ($query) => $query->whereNotIn('piva', ['', '---']));
    }

    public function test_placeholder_piva_values_do_not_conflict_with_each_other(): void
    {
        $record = Fornitore::where('piva', '---')->first();

        if (! $record) {
            $this->markTestSkipped('Nessun fornitore con piva placeholder "---" sul database proforma per il test.');
        }

        $validator = Validator::make(
            ['piva' => '---'],
            ['piva' => [$this->pivaUniqueRule()->ignore($record->id)]]
        );

        $this->assertFalse($validator->fails());
    }

    public function test_blank_piva_values_do_not_conflict_with_each_other(): void
    {
        $record = Fornitore::where(fn ($query) => $query->whereNull('piva')->orWhere('piva', ''))->first();

        if (! $record) {
            $this->markTestSkipped('Nessun fornitore con piva vuota sul database proforma per il test.');
        }

        $validator = Validator::make(
            ['piva' => ''],
            ['piva' => [$this->pivaUniqueRule()->ignore($record->id)]]
        );

        $this->assertFalse($validator->fails());
    }

    public function test_a_genuinely_duplicated_piva_still_fails_validation(): void
    {
        $duplicatedPiva = Fornitore::query()
            ->whereNotNull('piva')
            ->where('piva', '!=', '')
            ->where('piva', '!=', '---')
            ->select('piva')
            ->groupBy('piva')
            ->havingRaw('count(*) > 1')
            ->value('piva');

        if (! $duplicatedPiva) {
            $this->markTestSkipped('Nessuna partita IVA realmente duplicata sul database proforma per il test.');
        }

        $firstRecord = Fornitore::where('piva', $duplicatedPiva)->first();

        $validator = Validator::make(
            ['piva' => $duplicatedPiva],
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
