<?php

namespace App\Filament\Resources\DocumentSchedules\Pages;

use App\Filament\Resources\DocumentSchedules\DocumentScheduleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Contracts\Support\Htmlable;  // CORRETTO
use Illuminate\Support\HtmlString;

class ManageDocumentSchedules extends ManageRecords
{
    protected static string $resource = DocumentScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
        ];
    }

    public function getSubheading(): string|Htmlable|null
    {
        // $record = $this->getRecord();

        return new HtmlString('Per inviare solleciti selezionare con la checkbox i relativi documenti. Viene inviata una sola email per ogni destinatario con riepilogo di tutti i documenti selezionati');
    }
}
