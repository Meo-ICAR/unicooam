<?php

namespace Tests\Feature;

use App\Services\SharePoint\SharePointUploader;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class SharePointUploaderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.sharepoint.tenant_id' => 't',
            'services.sharepoint.client_id' => 'c',
            'services.sharepoint.client_secret' => 's',
            'services.sharepoint.drive_id' => 'D',
        ]);
        Cache::flush();
    }

    private function localFile(int $bytes): string
    {
        $path = tempnam(sys_get_temp_dir(), 'spup');
        file_put_contents($path, str_repeat('a', $bytes));

        return $path;
    }

    public function test_small_file_is_uploaded_with_a_single_put_and_folders_are_encoded(): void
    {
        Http::fake([
            'login.microsoftonline.com/*' => Http::response(['access_token' => 'tok']),
            'graph.microsoft.com/*' => Http::response(['id' => 'item1', 'eTag' => '"e1"', 'webUrl' => 'https://sp/item1']),
        ]);

        $result = (new SharePointUploader)->upload($this->localFile(100), ['Unicooam', 'Rossi/Mario'], 'Carta identità.pdf');

        $this->assertSame(['id' => 'item1', 'etag' => '"e1"', 'web_url' => 'https://sp/item1', 'drive_id' => 'D'], $result);
        Http::assertSent(fn ($r) => $r->method() === 'PUT'
            && str_contains($r->url(), 'drives/D/root:/Unicooam/Rossi-Mario/Carta%20identit%C3%A0.pdf:/content')
            && str_contains($r->url(), 'conflictBehavior=rename')
            && $r->hasHeader('Authorization', 'Bearer tok'));
    }

    public function test_large_file_uses_an_upload_session_in_chunks(): void
    {
        Http::fake([
            'login.microsoftonline.com/*' => Http::response(['access_token' => 'tok']),
            'graph.microsoft.com/*createUploadSession' => Http::response(['uploadUrl' => 'https://upload.sp/session1']),
            'upload.sp/session1' => Http::sequence()
                ->push(['nextExpectedRanges' => ['3276800-']], 202)
                ->push(['id' => 'big1', 'eTag' => '"e2"', 'webUrl' => 'https://sp/big1'], 201),
        ]);

        $result = (new SharePointUploader)->upload($this->localFile(4 * 1024 * 1024 + 10), ['Unicooam'], 'grande.pdf');

        $this->assertSame('big1', $result['id']);
        Http::assertSent(fn ($r) => $r->url() === 'https://upload.sp/session1'
            && $r->hasHeader('Content-Range', 'bytes 0-3276799/4194314')
            && ! $r->hasHeader('Authorization'));
        Http::assertSent(fn ($r) => $r->url() === 'https://upload.sp/session1'
            && $r->hasHeader('Content-Range', 'bytes 3276800-4194313/4194314'));
    }

    public function test_failed_upload_throws(): void
    {
        Http::fake([
            'login.microsoftonline.com/*' => Http::response(['access_token' => 'tok']),
            'graph.microsoft.com/*' => Http::response(['error' => 'denied'], 403),
        ]);

        $this->expectException(RuntimeException::class);

        (new SharePointUploader)->upload($this->localFile(10), ['Unicooam'], 'a.pdf');
    }

    public function test_sanitize_strips_forbidden_characters(): void
    {
        $uploader = new SharePointUploader;

        $this->assertSame('a-b-c', $uploader->sanitize('a/b:c'));
        $this->assertSame('_', $uploader->sanitize(' . '));
    }

    public function test_is_configured_requires_all_credentials(): void
    {
        $this->assertTrue((new SharePointUploader)->isConfigured());

        config(['services.sharepoint.drive_id' => null]);

        $this->assertFalse((new SharePointUploader)->isConfigured());
    }
}
