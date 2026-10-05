<?php

namespace App\Filament\Resources\OamPratiches\Pages;

use App\Filament\Resources\OamPratiches\OamPraticheResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditOamPratiche extends EditRecord
{
    protected static string $resource = OamPraticheResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
