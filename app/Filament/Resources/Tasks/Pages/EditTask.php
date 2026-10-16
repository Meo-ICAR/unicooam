<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditTask extends EditRecord
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importaDaSubtask')
                ->label('Popola da subtask')
                ->icon('heroicon-o-arrow-down-on-square-stack')
                ->color('gray')
                ->modalHeading('Popola con i documenti di un subtask')
                ->modalDescription('Copia in questo task i documenti richiesti dal subtask scelto, con destinazione e ruoli. I tipi già presenti restano come sono.')
                ->schema([
                    Select::make('source_id')
                        ->label('Subtask')
                        ->options(fn (): array => $this->getRecord()->children()->orderBy('name')->pluck('name', 'id')->all())
                        ->required()
                        ->searchable(),
                ])
                ->action(function (array $data): void {
                    $added = $this->getRecord()->importRequirementsFrom(Task::query()->findOrFail($data['source_id']));
                    $this->fillForm();

                    Notification::make()->title($added.' documenti aggiunti al task')->success()->send();
                }),
            DeleteAction::make(),
        ];
    }
}
