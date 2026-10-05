<?php

namespace Tests\Unit;

use App\Neuron\ManualAssistantAgent;
use Tests\TestCase;

class ManualAssistantAgentTest extends TestCase
{
    public function test_manual_sources_point_at_the_project_guidelines_and_the_oam_manual(): void
    {
        $sources = ManualAssistantAgent::manualSources();

        $this->assertSame([
            base_path('CLAUDE.md'),
            resource_path('manuals/manuale-operativo-oam.html'),
        ], $sources);

        foreach ($sources as $source) {
            $this->assertFileExists($source);
        }
    }
}
