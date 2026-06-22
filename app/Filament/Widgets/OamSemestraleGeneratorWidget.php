<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class OamSemestraleGeneratorWidget extends Widget
{
    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.oam-semestrale-generator-widget';
}
