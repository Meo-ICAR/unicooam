<?php

namespace App\Filament\Resources\OamCodes\Pages;

use App\Filament\Resources\OamCodes\OamCodeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOamCode extends EditRecord
{
    protected static string $resource = OamCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
