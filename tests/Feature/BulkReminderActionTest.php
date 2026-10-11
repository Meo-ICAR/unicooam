<?php

namespace Tests\Feature;

use App\Filament\Resources\DocumentSchedules\Pages\ManageDocumentSchedules;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentSchedule;
use App\Models\DocumentType;
use App\Models\EmailTemplate;
use App\Models\Employee;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;
use Unico\Core\Documents\Reminders\ReminderMail;

class BulkReminderActionTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function schedule(string $email): DocumentSchedule
    {
        $company = Company::factory()->create();
        $employee = Employee::query()->create(['company_id' => $company->id, 'name' => 'ROSSI MARIO', 'email' => $email]);
        $type = DocumentType::query()->create(['name' => 'Tipo '.Str::random(6), 'slug' => 'tipo-'.Str::random(6), 'priority' => 1]);
        $doc = Document::query()->create([
            'id' => (string) Str::uuid(), 'company_id' => $company->id, 'documentable_type' => 'employee', 'documentable_id' => (string) $employee->id,
            'document_type_id' => $type->id, 'name' => 'Casellario', 'status' => 'approvato', 'is_monitored' => true, 'expires_at' => now()->addDays(5)->toDateString(),
        ]);

        return DocumentSchedule::query()->create([
            'document_id' => $doc->id, 'documentable_group_key' => 'employee|'.$employee->id, 'document_name' => 'Casellario', 'document_type_name' => $type->name,
            'entity_name' => 'ROSSI MARIO', 'documentable_type' => 'employee', 'documentable_id' => (string) $employee->id,
            'expires_at' => now()->addDays(5)->toDateString(), 'days_until_expiry' => 5, 'status' => 'approvato', 'reminders_count' => 0,
        ]);
    }

    private function template(): EmailTemplate
    {
        return EmailTemplate::create(['code' => 'DOC_EXPIRING', 'name' => 'Scadenza', 'subject' => 'Ciao {agente_nome}', 'body' => '<p>{n_documenti} documenti</p>{elenco_documenti}', 'is_active' => true]);
    }

    public function test_the_bulk_reminder_is_sent_through_the_package_and_counted(): void
    {
        Mail::fake();
        $template = $this->template();
        $row = $this->schedule('rossi@example.com');
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $this->actingAs($admin);

        Livewire::test(ManageDocumentSchedules::class)
            ->selectTableRecords([$row->getKey()])
            ->callAction(TestAction::make('inviaSollecito')->table()->bulk(), ['email_template_id' => $template->id, 'subject' => $template->subject, 'body' => $template->body, 'is_demo' => false])
            ->assertHasNoActionErrors();

        Mail::assertSent(ReminderMail::class, fn (ReminderMail $mail): bool => $mail->hasTo('rossi@example.com') && $mail->hasCc('admin@example.com')
            && $mail->subjectLine === 'Ciao ROSSI MARIO' && str_contains($mail->bodyContent, '1 documenti') && str_contains($mail->bodyContent, 'Casellario'));
        $this->assertSame(1, (int) $row->document->fresh()->reminders_count);
    }

    public function test_demo_goes_to_the_operator_and_does_not_count(): void
    {
        Mail::fake();
        $template = $this->template();
        $row = $this->schedule('rossi@example.com');
        $this->actingAs(User::factory()->create(['email' => 'admin@example.com']));

        Livewire::test(ManageDocumentSchedules::class)
            ->selectTableRecords([$row->getKey()])
            ->callAction(TestAction::make('inviaSollecito')->table()->bulk(), ['email_template_id' => $template->id, 'subject' => 'Oggetto', 'body' => '<p>Testo</p>', 'is_demo' => true]);

        Mail::assertSent(ReminderMail::class, fn (ReminderMail $mail): bool => $mail->hasTo('admin@example.com') && str_starts_with($mail->subjectLine, '[DEMO] '));
        $this->assertSame(0, (int) $row->document->fresh()->reminders_count);
    }
}
