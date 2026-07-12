<?php

namespace App\Filament\Unicofin\Resources\Provvigiones\Pages;

use App\Filament\Unicofin\Resources\Provvigiones\ProvvigioneResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProvvigione extends EditRecord
{
    protected static string $resource = ProvvigioneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
