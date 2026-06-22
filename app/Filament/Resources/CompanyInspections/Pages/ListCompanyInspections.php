<?php

namespace App\Filament\Resources\CompanyInspections\Pages;

use App\Filament\Resources\CompanyInspections\CompanyInspectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCompanyInspections extends ListRecords
{
    protected static string $resource = CompanyInspectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
