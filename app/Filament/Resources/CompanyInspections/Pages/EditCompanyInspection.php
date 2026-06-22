<?php

namespace App\Filament\Resources\CompanyInspections\Pages;

use App\Filament\Resources\CompanyInspections\CompanyInspectionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCompanyInspection extends EditRecord
{
    protected static string $resource = CompanyInspectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
