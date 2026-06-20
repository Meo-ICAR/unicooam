<?php

namespace App\Filament\Resources\OamSemestrales\Pages;

use App\Filament\Resources\OamSemestrales\OamSemestraleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOamSemestrale extends EditRecord
{
    protected static string $resource = OamSemestraleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
