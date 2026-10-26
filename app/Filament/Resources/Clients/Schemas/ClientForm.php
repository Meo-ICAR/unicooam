<?php

namespace App\Filament\Resources\Clients\Schemas;

use App\Models\EmployeeType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Anagrafica')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nominativo / Ragione sociale')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->nullable()
                            ->maxLength(255),
                        Toggle::make('is_person')
                            ->label('Persona fisica')
                            ->helperText('Spento = società o studio (persona giuridica).')
                            ->default(true)
                            ->live(),
                        TextInput::make('tax_code')
                            ->label('Codice fiscale')
                            ->nullable()
                            ->maxLength(16),
                        TextInput::make('vat_number')
                            ->label('Partita IVA')
                            ->nullable()
                            ->regex('/^\d{11}$/')
                            ->validationMessages(['regex' => 'La partita IVA ha 11 cifre.'])
                            ->maxLength(20),
                        Select::make('employer_id')
                            ->label('Datore di lavoro')
                            ->helperText('Per le persone fisiche: la società o lo studio in cui lavorano (es. Ancora S.r.l.).')
                            ->relationship('employer', 'name', fn (Builder $query) => $query->where('is_person', false))
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->visible(fn (Get $get): bool => (bool) $get('is_person')),
                        Select::make('employee_roles')
                            ->label('Ruoli')
                            ->multiple()
                            ->options(
                                EmployeeType::query()
                                    ->pluck('name', 'name')
                                    ->map(fn (string $name) => ucfirst($name))
                            )
                            ->searchable()
                            ->nullable()
                            ->helperText('Ruoli dell\'organigramma ricoperti da questo consulente (stessa struttura dei dipendenti).'),
                    ]),
            ]);
    }
}
