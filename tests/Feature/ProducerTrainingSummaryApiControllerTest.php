<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\ProducerTrainingSummaryApiController;
use App\Mail\ProducerTrainingSummaryMail;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ProducerTrainingSummaryApiControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_email_body_lists_producers_with_their_hours_and_defaults_to_zero(): void
    {
        $rows = new Collection([
            ['name' => 'Rossi Mario', 'email' => 'rossi@example.com', 'training_hours' => 12],
            ['name' => 'Bianchi <Test>', 'email' => null, 'training_hours' => 0],
        ]);

        $body = ProducerTrainingSummaryApiController::buildEmailBody($rows);

        $this->assertStringContainsString('Rossi Mario', $body);
        $this->assertStringContainsString('rossi@example.com', $body);
        $this->assertStringContainsString('<td>12</td>', $body);
        $this->assertStringContainsString('<td>0</td>', $body);
        $this->assertStringContainsString('Bianchi &lt;Test&gt;', $body);
    }

    public function test_endpoint_emails_the_summary_to_the_expected_recipient(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/fornitori/training-summary-email');

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);

        Mail::assertSent(ProducerTrainingSummaryMail::class, function (ProducerTrainingSummaryMail $mail) {
            return $mail->hasTo('hassistosrl@gmail.com');
        });
    }
}
