<?php

namespace App\Filament\Unicofin\Resources\Provvigiones\Pages;

use App\Filament\Unicofin\Resources\Provvigiones\ProvvigioneResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProvvigiones extends ListRecords
{
    protected static string $resource = ProvvigioneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
