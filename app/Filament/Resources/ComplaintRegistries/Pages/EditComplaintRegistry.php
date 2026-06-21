<?php

namespace App\Filament\Resources\ComplaintRegistries\Pages;

use App\Filament\Resources\ComplaintRegistries\ComplaintRegistryResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditComplaintRegistry extends EditRecord
{
    protected static string $resource = ComplaintRegistryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
