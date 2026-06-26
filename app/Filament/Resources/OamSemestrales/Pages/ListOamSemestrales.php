<?php

namespace App\Filament\Resources\OamSemestrales\Pages;

use App\Filament\Actions\ExportOamAction;
use App\Filament\Actions\ImportOamAction;
use App\Filament\Resources\OamSemestrales\OamSemestraleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;  // CORRETTO
use Illuminate\Support\HtmlString;

class ListOamSemestrales extends ListRecords
{
    protected static string $resource = OamSemestraleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportOamAction::make()
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('info'),
            ExportOamAction::make(),
            //    CreateAction::make(),
        ];
    }
}
