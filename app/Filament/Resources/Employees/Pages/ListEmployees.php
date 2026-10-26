<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\Client;
use App\Models\EmployeeType;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Set;

// CORRETTO

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('nuovoConsulente')
                ->label('Nuovo consulente')
                ->icon('heroicon-o-user-plus')
                ->color('gray')
                ->url(fn (): string => ClientResource::getUrl('create')),
            Action::make('ruoliEsterno')
                ->label('Ruoli di un esterno')
                ->icon('heroicon-o-briefcase')
                ->color('gray')
                ->modalHeading('Ruoli di un consulente esterno')
                ->modalDescription('Un consulente esterno può ricoprire solo i ruoli esternalizzabili (es. Internal Audit, Compliance, AML, DPO). Chi ricopre un ruolo è un consulente aziendale.')
                ->modalSubmitActionLabel('Salva i ruoli')
                ->schema([
                    Select::make('client_id')
                        ->label('Consulente esterno')
                        ->helperText('Si scelgono solo i consulenti aziendali; per aggiungerne uno nuovo si crea il client e gli si assegna un ruolo.')
                        ->options(fn (): array => Client::query()->where('is_company', true)->orderBy('name')->limit(50)->pluck('name', 'id')->all())
                        ->getSearchResultsUsing(fn (string $search): array => Client::query()->where('is_company', true)->where('name', 'like', "%{$search}%")->orderBy('name')->limit(50)->pluck('name', 'id')->all())
                        ->getOptionLabelUsing(fn ($value): ?string => Client::query()->find($value)?->name)
                        ->searchable()
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn ($state, Set $set) => $set('roles', array_values(array_intersect(
                            (array) Client::query()->find($state)?->employee_roles,
                            self::outsourcableRoles(),
                        )))),
                    Select::make('roles')
                        ->label('Ruoli')
                        ->multiple()
                        ->options(fn (): array => EmployeeType::query()->whereIn('name', self::outsourcableRoles())->orderBy('name')->get()
                            ->mapWithKeys(fn (EmployeeType $role): array => [$role->name => $role->description ? "{$role->description} ({$role->name})" : $role->name])
                            ->all()),
                ])
                ->action(function (array $data): void {
                    $client = Client::query()->findOrFail($data['client_id']);
                    $others = array_values(array_diff((array) $client->employee_roles, self::outsourcableRoles()));
                    $client->update(['employee_roles' => array_values(array_unique([...$others, ...(array) ($data['roles'] ?? [])]))]);

                    Notification::make()->title('Ruoli di '.$client->name.' aggiornati')->success()->send();
                }),
        ];
    }

    /**
     * Nomi dei ruoli che si possono affidare a un esterno.
     *
     * @return list<string>
     */
    private static function outsourcableRoles(): array
    {
        return EmployeeType::query()->whereIn('outsourcable_status', ['yes', 'partial'])->pluck('name')->all();
    }
}
