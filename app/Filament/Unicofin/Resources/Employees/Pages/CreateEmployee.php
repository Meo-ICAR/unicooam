<?php

namespace App\Filament\Unicofin\Resources\Employees\Pages;

use App\Filament\Unicofin\Resources\Employees\EmployeeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;
}
