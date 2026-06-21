<?php

namespace App\Filament\Resources\ComplaintRegistries\Pages;

use App\Filament\Resources\ComplaintRegistries\ComplaintRegistryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateComplaintRegistry extends CreateRecord
{
    protected static string $resource = ComplaintRegistryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = filament()->getTenant()?->id;
        return $data;
    }
}
