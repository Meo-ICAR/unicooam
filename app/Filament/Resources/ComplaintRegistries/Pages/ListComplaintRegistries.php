<?php

namespace App\Filament\Resources\ComplaintRegistries\Pages;

use App\Filament\Resources\ComplaintRegistries\ComplaintRegistryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;  // CORRETTO
use Illuminate\Support\HtmlString;

class ListComplaintRegistries extends ListRecords
{
    protected static string $resource = ComplaintRegistryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
