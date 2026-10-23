<?php

namespace Tests\Feature;

use App\Models\PROFORMA\Clienti;
use Illuminate\Database\Eloquent\Relations\Relation;
use Tests\TestCase;
use Unico\Core\Models\AuditChecklistEvaluation;

class DocumentSubjectMorphMapTest extends TestCase
{
    public function test_legacy_istituto_alias_resolves_and_new_records_keep_cliente(): void
    {
        $istituto = new Clienti;

        $this->assertSame(Clienti::class, Relation::getMorphedModel('clienti'));
        $this->assertSame(Clienti::class, Relation::getMorphedModel('cliente'));
        $this->assertSame('cliente', $istituto->getMorphClass());
    }

    public function test_checklist_evaluation_alias_resolves(): void
    {
        $this->assertSame(AuditChecklistEvaluation::class, Relation::getMorphedModel('audit_checklist_evaluation'));
    }
}
