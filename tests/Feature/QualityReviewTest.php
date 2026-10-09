<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\QualityReviews\Pages\EditQualityReview;
use App\Filament\Resources\QualityReviews\Pages\ListQualityReviews;
use App\Filament\Resources\QualityReviews\RelationManagers\AuditsRelationManager;
use App\Models\Audit;
use App\Models\AuditFinding;
use App\Models\Company;
use App\Models\PROFORMA\Pratica;
use App\Models\QualityReview;
use App\Models\User;
use App\Services\QualityReviewSampler;
use App\ValueObjects\OamSemester;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QualityReviewTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function requirePratiche(): void
    {
        if (Pratica::query()->count() < 5) {
            $this->markTestSkipped('Servono almeno 5 pratiche sul database proforma (sola lettura).');
        }
    }

    public function test_sampler_extracts_n_distinct_pratiche_within_filters(): void
    {
        $this->requirePratiche();
        Company::factory()->create();
        $from = '2025-01-01';

        $review = app(QualityReviewSampler::class)->createReview('Test', 5, [
            'period_from' => $from,
            'escludi_gia_campionate' => true,
        ]);

        $audits = $review->audits;
        $this->assertCount(5, $audits);
        $this->assertCount(5, $audits->pluck('auditable_id')->unique());
        $this->assertSame(['quality'], $audits->pluck('origin_type')->unique()->all());
        $this->assertSame(['pratica'], $audits->pluck('auditable_type')->unique()->all());
        $this->assertTrue($audits->every(fn (Audit $a) => $a->auditable->data_inserimento_pratica->gte($from)));
    }

    public function test_already_sampled_pratiche_are_excluded_from_next_sample(): void
    {
        $this->requirePratiche();
        Company::factory()->create();
        $sampler = app(QualityReviewSampler::class);

        $first = $sampler->createReview('Primo', 5, []);
        $second = $sampler->createReview('Secondo', 5, []);

        $this->assertEmpty($first->audits->pluck('auditable_id')->intersect($second->audits->pluck('auditable_id')));
    }

    public function test_sample_is_capped_by_available_candidates(): void
    {
        $this->requirePratiche();
        Company::factory()->create();

        $review = app(QualityReviewSampler::class)->createReview('Vuoto', 5, ['period_from' => '2999-01-01']);

        $this->assertSame(0, $review->sample_size);
        $this->assertCount(0, $review->audits);
    }

    public function test_quality_audits_are_excluded_from_oam_rilievi(): void
    {
        $company = Company::factory()->create();
        Audit::factory()->create([
            'company_id' => $company->id, 'auditable_id' => $company->id,
            'origin_type' => 'quality', 'outcome' => 'con_rilievi', 'executed_at' => now(),
        ]);

        $semester = OamSemester::current();
        $this->assertSame(0, Audit::rilieviOam($semester)->count());
    }

    public function test_quality_user_can_open_list_and_extract_sample(): void
    {
        $this->requirePratiche();
        Company::factory()->create();
        $user = User::factory()->create(['role' => UserRole::QUALITY->value]);
        $this->actingAs($user);

        Livewire::test(ListQualityReviews::class)
            ->callAction('estraiCampione', [
                'name' => 'Q1',
                'sample_size' => 3,
                'period_from' => '2025-01-01',
                'escludi_gia_campionate' => true,
                'reviewer_user_id' => $user->id,
            ])
            ->assertHasNoActionErrors();

        $review = QualityReview::firstOrFail();
        $this->assertSame($user->id, $review->reviewer_user_id);
        $this->assertCount(3, $review->audits);
    }

    public function test_reviewer_can_evaluate_pratica_and_findings_attach_to_audit(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['role' => UserRole::QUALITY->value]);
        $this->actingAs($user);

        $review = QualityReview::factory()->create(['company_id' => $company->id]);
        $audit = Audit::factory()->create([
            'company_id' => $company->id, 'auditable_id' => $company->id, 'quality_review_id' => $review->id,
            'origin_type' => 'quality', 'outcome' => null, 'executed_at' => null,
        ]);

        Livewire::test(AuditsRelationManager::class, ['ownerRecord' => $review, 'pageClass' => EditQualityReview::class])
            ->callAction(TestAction::make('valuta')->table($audit), ['outcome' => 'con_rilievi', 'auditor_notes' => 'Manca il documento'])
            ->assertHasNoActionErrors();

        $audit->refresh();
        $this->assertSame('con_rilievi', $audit->outcome);
        $this->assertSame('Manca il documento', $audit->auditor_notes);
        $this->assertNotNull($audit->executed_at);

        AuditFinding::factory()->create(['audit_id' => $audit->id, 'company_id' => $company->id]);
        $this->assertSame(1, $audit->findings()->count());
        $this->assertSame(1, $review->reviewedCount());
    }

    public function test_excel_export_actions_are_available(): void
    {
        $company = Company::factory()->create();
        $this->actingAs(User::factory()->create(['role' => UserRole::QUALITY->value]));
        $review = QualityReview::factory()->create(['company_id' => $company->id]);

        Livewire::test(ListQualityReviews::class)->assertTableActionExists('export');

        Livewire::test(AuditsRelationManager::class, ['ownerRecord' => $review, 'pageClass' => EditQualityReview::class])
            ->assertSuccessful();
    }
}
