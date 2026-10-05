<?php

namespace App\Filament\Resources\SuspiciousActivityReports\Pages;

use App\Filament\Resources\SuspiciousActivityReports\SuspiciousActivityReportResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditSuspiciousActivityReport extends EditRecord
{
    protected static string $resource = SuspiciousActivityReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
