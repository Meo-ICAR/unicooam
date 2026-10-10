<?php

namespace Tests\Feature;

use App\Models\DocumentType;
use App\Models\PROFORMA\Pratica;
use App\Models\PraticaRequisito;
use App\Models\PraticaRequisitoOperativo;
use App\Models\RequisitoTipoFinanziamento;
use App\Models\Task;
use App\Models\Tipoprodotto;
use App\Models\TipoprodottoSub;
use Database\Seeders\PraticaRequisitiSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * I requisiti documentali di pratiche e task stanno nelle tabelle del pacchetto
 * (document_types, document_requirements, document_requests).
 */
class PraticaRequisitiTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function sub(): TipoprodottoSub
    {
        $prodotto = Tipoprodotto::create(['name' => 'Cessione del quinto']);

        return TipoprodottoSub::create(['tipoprodotto_id' => $prodotto->id, 'name' => 'Stipendio']);
    }

    public function test_seeder_creates_requirements_as_document_types(): void
    {
        $this->seed(PraticaRequisitiSeeder::class);
        $this->seed(PraticaRequisitiSeeder::class);

        $polizza = PraticaRequisito::where('code', 'polizza_vita')->sole();

        $this->assertSame('Polizza Rischio Vita', $polizza->name);
        $this->assertSame('polizza_vita', $polizza->codice);
        $this->assertStringContainsString('premorienza', $polizza->descrizione);
        $this->assertSame(1, DocumentType::where('code', 'polizza_vita')->count());
    }

    public function test_product_rules_link_a_sub_product_to_its_requirements(): void
    {
        $sub = $this->sub();
        $b = PraticaRequisito::create(['name' => 'B', 'code' => 'b']);
        $a = PraticaRequisito::create(['name' => 'A', 'code' => 'a']);
        RequisitoTipoFinanziamento::create(['tipoprodotto_sub_id' => $sub->id, 'document_type_id' => $b->id, 'is_required' => false, 'sort_order' => 20]);
        RequisitoTipoFinanziamento::create(['tipoprodotto_sub_id' => $sub->id, 'document_type_id' => $a->id, 'is_required' => true, 'sort_order' => 10]);

        $requisiti = $sub->requisiti;

        $this->assertSame(['a', 'b'], $requisiti->pluck('code')->all());
        $this->assertTrue((bool) $requisiti[0]->pivot->is_required);
        $this->assertFalse((bool) $requisiti[1]->pivot->is_required);
        $this->assertCount(2, $sub->regoleRequisiti);
        $this->assertSame(['B', 'A'], $sub->regoleRequisiti->map(fn ($regola) => $regola->requisito->name)->all());
    }

    public function test_a_pratica_generates_its_operational_requirements_without_touching_proforma(): void
    {
        $sub = $this->sub();
        $a = PraticaRequisito::create(['name' => 'A', 'code' => 'a']);
        $b = PraticaRequisito::create(['name' => 'B', 'code' => 'b']);
        RequisitoTipoFinanziamento::create(['tipoprodotto_sub_id' => $sub->id, 'document_type_id' => $a->id, 'is_required' => true, 'sort_order' => 1]);
        RequisitoTipoFinanziamento::create(['tipoprodotto_sub_id' => $sub->id, 'document_type_id' => $b->id, 'is_required' => false, 'sort_order' => 2]);

        $pratica = new Pratica;
        $pratica->id = 'QT00001';
        $pratica->tipoprodotto_sub_id = $sub->id;

        $pratica->generaRequisitiDaProdotto();
        $pratica->generaRequisitiDaProdotto();

        $righe = PraticaRequisitoOperativo::orderBy('id')->get();
        $this->assertCount(2, $righe);
        $this->assertSame(['pratica', 'pratica'], $righe->pluck('requestable_type')->all());
        $this->assertSame(['QT00001', 'QT00001'], $righe->pluck('requestable_id')->all());
        $this->assertSame(['da_richiedere', 'da_richiedere'], $righe->pluck('status')->all());
        $this->assertSame([true, false], $righe->pluck('is_required')->map(fn ($v) => (bool) $v)->all());

        $this->assertTrue($pratica->haRequisitiObbligatoriIncompleti());
        $this->assertSame(0, $pratica->percentuale_completamento_requisiti);
        $this->assertSame([$a->id], $pratica->getRequisitiObbligatoriMancanti()->pluck('document_type_id')->all());

        $righe[0]->segnaComeApprovato('ok');
        $righe[1]->segnaComeRichiesto();

        $this->assertFalse($pratica->haRequisitiObbligatoriIncompleti());
        $this->assertSame(50, $pratica->percentuale_completamento_requisiti);
        $this->assertSame('ok', $righe[0]->fresh()->notes);
        $this->assertNotNull($righe[0]->fresh()->completed_at);
        $this->assertCount(1, PraticaRequisitoOperativo::aperti()->get());
        $this->assertCount(1, PraticaRequisitoOperativo::completati()->get());
        $this->assertCount(1, PraticaRequisitoOperativo::obbligatori()->get());
        $this->assertSame('A', $righe[0]->requisito->name);
    }

    public function test_task_documents_are_requirements_with_a_task(): void
    {
        $task = Task::create(['name' => 'onboarding']);
        $tipo = DocumentType::create(['name' => 'Carta identità', 'code' => 'ci']);

        $task->documentTypes()->attach([$tipo->id => ['is_required' => true]]);

        $this->assertSame(['Carta identità'], $task->documentTypes()->pluck('document_types.name')->all());
        $this->assertTrue((bool) $task->documentTypes->first()->pivot->is_required);
        $this->assertSame(['onboarding'], $tipo->tasks()->pluck('tasks.name')->all());
    }
}
