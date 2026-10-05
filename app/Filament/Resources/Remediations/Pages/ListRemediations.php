<?php

namespace App\Filament\Resources\Remediations\Pages;

use App\Filament\Resources\Remediations\RemediationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;  // CORRETTO
use Illuminate\Support\HtmlString;

class ListRemediations extends ListRecords
{
    protected static string $resource = RemediationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
