<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Resources\Companies\CompanyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCompany extends EditRecord
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            Action::make('createtask')
                ->label('Crea plico')
                ->icon('heroicon-o-document-plus')
                ->form([
                    Select::make('task_id')
                        ->label('Seleziona il Task')
                        ->options(fn($record) => Task::getAvailableFor($record)->pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                ])
        ];
    }
}
