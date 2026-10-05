<?php

namespace App\Filament\Resources\OamCodes\Pages;

use App\Filament\Resources\OamCodes\OamCodeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;  // CORRETTO
use Illuminate\Support\HtmlString;

class ListOamCodes extends ListRecords
{
    protected static string $resource = OamCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getSubheading(): string|Htmlable|null
    {
        // $record = $this->getRecord();

        return new HtmlString('Sunto convenzioni per tipologia OAM');
    }
}
