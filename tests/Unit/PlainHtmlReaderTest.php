<?php

namespace Tests\Unit;

use App\Neuron\PlainHtmlReader;
use Tests\TestCase;

class PlainHtmlReaderTest extends TestCase
{
    public function test_strips_tags_and_keeps_text_readable(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'html_');
        file_put_contents($file, '<html><head><style>body{color:red}</style></head>'
            .'<body><h1>Titolo</h1><p>Prima riga.</p><p>Seconda riga con &egrave; accentata.</p>'
            .'<script>alert(1)</script></body></html>');

        $text = PlainHtmlReader::getText($file);
        unlink($file);

        $this->assertStringNotContainsString('<', $text);
        $this->assertStringNotContainsString('color:red', $text);
        $this->assertStringNotContainsString('alert(1)', $text);
        $this->assertStringContainsString('Titolo', $text);
        $this->assertStringContainsString('Prima riga.', $text);
        $this->assertStringContainsString('è accentata.', $text);
    }

    public function test_real_manual_file_is_extracted_without_html_noise(): void
    {
        $path = resource_path('manuals/manuale-operativo-oam.html');

        $text = PlainHtmlReader::getText($path);

        $this->assertStringNotContainsString('<h2', $text);
        $this->assertStringContainsString('Registro reclami', $text);
        $this->assertStringContainsString('OAM', $text);
    }
}
