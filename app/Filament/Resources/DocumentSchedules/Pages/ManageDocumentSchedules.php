<?php

namespace App\Filament\Resources\DocumentSchedules\Pages;

use App\Filament\Resources\DocumentSchedules\DocumentScheduleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageDocumentSchedules extends ManageRecords
{
    protected static string $resource = DocumentScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
        ];
    }
}
