<?php

namespace App\Filament\Unicofin\Resources\Praticas\Pages;

use App\Filament\Unicofin\Resources\Praticas\PraticaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPratica extends EditRecord
{
    protected static string $resource = PraticaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
