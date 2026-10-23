<?php

namespace Tests\Feature;

use App\Models\Pratica;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * Un plico si applica ai record del model collegato (`taskable`) che passano le due condizioni di attivazione.
 */
class TaskConditionsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_both_trigger_conditions_must_hold(): void
    {
        $task = new Task([
            'taskable' => 'pratica',
            'trigger_field' => 'tipo_prodotto',
            'trigger_state' => 'equals',
            'trigger_value' => 'Cessione',
            'trigger_subfield' => 'stato_pratica',
            'trigger_subvalue' => 'PERFEZIONATA',
        ]);

        $this->assertTrue($task->matchesConditions(new Pratica(['tipo_prodotto' => 'Cessione', 'stato_pratica' => 'PERFEZIONATA'])));
        $this->assertFalse($task->matchesConditions(new Pratica(['tipo_prodotto' => 'Cessione', 'stato_pratica' => 'APERTA'])));
        $this->assertFalse($task->matchesConditions(new Pratica(['tipo_prodotto' => 'Mutuo', 'stato_pratica' => 'PERFEZIONATA'])));
    }

    public function test_record_of_another_model_is_rejected_and_exclusion_applies(): void
    {
        $task = new Task(['taskable' => 'pratica', 'exclude_field' => 'stato_pratica', 'exclude_state' => 'filled']);

        $this->assertTrue($task->matchesConditions(new Pratica));
        $this->assertFalse($task->matchesConditions(new Pratica(['stato_pratica' => 'APERTA'])));
        $this->assertFalse($task->matchesConditions(new User));
    }
}
