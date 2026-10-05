<?php

namespace Tests\Feature;

use App\Models\Document;
use Tests\TestCase;

class DocumentResolvedUrlTest extends TestCase
{
    public function test_resolved_url_prefers_document_url(): void
    {
        $document = new Document([
            'document_url' => 'https://example.test/a.pdf',
            'metadata' => ['web_url' => 'https://sharepoint.test/b.pdf'],
        ]);

        $this->assertSame('https://example.test/a.pdf', $document->resolved_url);
    }

    public function test_resolved_url_falls_back_to_metadata_web_url_when_document_url_is_empty(): void
    {
        foreach ([null, ''] as $emptyUrl) {
            $document = new Document([
                'document_url' => $emptyUrl,
                'metadata' => ['web_url' => 'https://sharepoint.test/b.pdf'],
            ]);

            $this->assertSame('https://sharepoint.test/b.pdf', $document->resolved_url);
        }
    }

    public function test_resolved_url_is_null_without_any_url(): void
    {
        $this->assertNull((new Document)->resolved_url);
        $this->assertNull((new Document(['metadata' => ['path' => 'x']]))->resolved_url);
    }
}
