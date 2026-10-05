<?php

namespace App\Filament\Resources\AuditFindings\Pages;

use App\Filament\Resources\AuditFindings\AuditFindingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAuditFinding extends CreateRecord
{
    protected static string $resource = AuditFindingResource::class;
}
