<?php

namespace Tests\Feature;

use App\Models\Document;
use Tests\TestCase;

/**
 * Il Document dell'app eredita da quello del pacchetto le collection `documents` e `signed` sul disco privato:
 * se l'app ridichiara il trait InteractsWithMedia, il suo registerMediaCollections() vuoto le nasconde.
 */
class DocumentMediaCollectionsTest extends TestCase
{
    public function test_the_package_collections_are_registered_on_the_private_disk(): void
    {
        $document = new Document;
        $disk = config('unico-core.documents_disk');

        $documents = $document->getMediaCollection('documents');
        $signed = $document->getMediaCollection('signed');

        $this->assertNotNull($documents);
        $this->assertNotNull($signed);
        $this->assertSame($disk, $documents->diskName);
        $this->assertSame($disk, $signed->diskName);
        $this->assertTrue($signed->singleFile);
        $this->assertNotSame('public', $disk);
    }
}
