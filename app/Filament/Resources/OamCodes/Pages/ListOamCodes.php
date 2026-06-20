<?php

namespace App\Filament\Resources\OamCodes\Pages;

use App\Filament\Resources\OamCodes\OamCodeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOamCodes extends ListRecords
{
    protected static string $resource = OamCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
