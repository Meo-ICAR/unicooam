<?php

namespace Tests\Feature;

use App\Mail\ScadenziarioReportMail;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DocumentScheduleSyncApiControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_syncs_the_schedule_and_emails_the_excel_report(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/document-schedules/sync-and-email');

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);

        Mail::assertSent(ScadenziarioReportMail::class, function (ScadenziarioReportMail $mail) {
            return $mail->hasTo('hassistosrl@gmail.com')
                && $mail->hasCc('piergiuseppe.meo@gmail.com')
                && $mail->fileName !== ''
                && $mail->fileContents !== '';
        });
    }
}
