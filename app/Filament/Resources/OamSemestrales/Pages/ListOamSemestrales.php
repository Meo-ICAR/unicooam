<?php

namespace App\Filament\Resources\OamSemestrales\Pages;

use App\Filament\Resources\OamSemestrales\OamSemestraleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOamSemestrales extends ListRecords
{
    protected static string $resource = OamSemestraleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
