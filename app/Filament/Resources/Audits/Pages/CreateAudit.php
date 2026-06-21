<?php

namespace App\Filament\Admin\Resources\Audits\Pages;

use App\Filament\Admin\Resources\Audits\AuditResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAudit extends CreateRecord
{
    protected static string $resource = AuditResource::class;
}
