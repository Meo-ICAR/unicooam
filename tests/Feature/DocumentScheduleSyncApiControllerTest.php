<?php

namespace Tests\Feature;

use App\Mail\ScadenziarioReportMail;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DocumentScheduleSyncApiControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_the_same_job_is_available_through_the_bridge_with_the_key(): void
    {
        config(['unico-core.bridge.api_key' => 'chiave-prova']);
        Mail::fake();

        $this->postJson('/api/commands/documents:sync-and-email', [], ['X-Api-Key' => 'chiave-prova'])->assertAccepted()->assertJsonStructure(['run_id', 'status_url']);
        $this->postJson('/api/commands/documents:sync-and-email')->assertUnauthorized();
        $this->postJson('/api/commands/db:wipe', [], ['X-Api-Key' => 'chiave-prova'])->assertNotFound();

        Mail::assertSent(ScadenziarioReportMail::class);
    }

    public function test_syncs_the_schedule_and_emails_the_excel_report(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/document-schedules/sync-and-email');

        // In coda: nei test la coda è sincrona, quindi il lavoro è già fatto.
        $response->assertAccepted()->assertJson(['status' => 'queued']);
        $this->assertSame('succeeded', \Unico\Core\Models\CommandRun::findOrFail($response->json('run_id'))->status);

        Mail::assertSent(ScadenziarioReportMail::class, function (ScadenziarioReportMail $mail) {
            return $mail->hasTo('hassistosrl@gmail.com')
                && $mail->hasCc('piergiuseppe.meo@gmail.com')
                && $mail->fileName !== ''
                && $mail->fileContents !== '';
        });
    }
}
