<?php

namespace App\Filament\Admin\Resources\Audits\Schemas;

use App\Models\Audit;
use App\Models\Client;
use App\Models\Company;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class AuditForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('audit_tabs')->tabs([

                Tab::make('Audit')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->schema([
                        Section::make('Tipo e direzione')
                            ->columns(2)
                            ->schema([
                                TextInput::make('title')
                                    ->label('Titolo audit')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Es. Audit annuale responsabile del trattamento — Mario Rossi Srl')
                                    ->columnSpanFull(),

                                Select::make('direction')
                                    ->label('Direzione')
                                    ->required()
                                    ->options(Audit::getDirectionOptions())
                                    ->native(false)
                                    ->live()
                                    ->helperText('Outgoing = noi auditiamo; Incoming = riceviamo audit'),

                                Select::make('status')
                                    ->label('Stato')
                                    ->required()
                                    ->options(Audit::getStatusOptions())
                                    ->native(false)
                                    ->default('planned'),
                            ]),

                        Section::make('Soggetto auditato')
                            ->description('Per audit outgoing: il cliente responsabile del trattamento che stiamo auditando')
                            ->columns(2)
                            ->visible(fn(Get $get) => $get('direction') === 'outgoing')
                            ->schema([
                                Select::make('auditable_type')
                                    ->label('Tipo soggetto')
                                    ->options([Client::class => 'Cliente (responsabile del trattamento)'])
                                    ->default(Client::class)
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(fn(callable $set) => $set('auditable_id', null)),

                                Select::make('auditable_id')
                                    ->label('Cliente')
                                    ->searchable()
                                    ->getSearchResultsUsing(fn(string $search) =>
                                        Client::query()
                                            ->where(fn($q) => $q
                                                ->where('name', 'like', "%{$search}%")
                                                ->orWhere('first_name', 'like', "%{$search}%")
                                            )
                                            ->limit(30)->get()
                                            ->mapWithKeys(fn($c) => [$c->id => trim("{$c->first_name} {$c->name}")])
                                            ->toArray()
                                    )
                                    ->getOptionLabelUsing(fn($v) => optional(Client::find($v))->name ?? $v)
                                    ->nullable(),
                            ]),

                        Section::make('Origine audit ricevuto')
                            ->description('Per audit incoming: chi ci sta auditando')
                            ->columns(2)
                            ->visible(fn(Get $get) => $get('direction') === 'incoming')
                            ->schema([
                                Select::make('authority_type')
                                    ->label('Tipo autorità / richiedente')
                                    ->options(Audit::getAuthorityTypeOptions())
                                    ->native(false)
                                    ->live()
                                    ->nullable(),

                                TextInput::make('authority_name')
                                    ->label('Nome autorità / cliente richiedente')
                                    ->maxLength(255)
                                    ->placeholder('Es. Garante Privacy, Nome Cliente...')
                                    ->nullable(),

                                // Se incoming da client → collega il client
                                Select::make('auditable_id')
                                    ->label('Cliente richiedente')
                                    ->searchable()
                                    ->visible(fn(Get $get) => $get('authority_type') === 'client')
                                    ->getSearchResultsUsing(fn(string $search) =>
                                        Client::query()
                                            ->where('name', 'like', "%{$search}%")
                                            ->limit(30)->pluck('name', 'id')->toArray()
                                    )
                                    ->getOptionLabelUsing(fn($v) => optional(Client::find($v))->name ?? $v)
                                    ->afterStateUpdated(function (callable $set) {
                                        $set('auditable_type', Client::class);
                                    })
                                    ->nullable()
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Date')
                            ->columns(2)
                            ->schema([
                                DatePicker::make('audit_date')
                                    ->label('Data audit')
                                    ->required()
                                    ->displayFormat('d/m/Y')
                                    ->default(now()),

                                DatePicker::make('followup_date')
                                    ->label('Data follow-up prevista')
                                    ->displayFormat('d/m/Y')
                                    ->nullable(),
                            ]),
                    ]),

                Tab::make('Perimetro & Note')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Section::make()->schema([
                            Textarea::make('scope')
                                ->label('Perimetro / oggetto dell\'audit')
                                ->rows(4)
                                ->placeholder('Descrivi cosa viene verificato: trattamenti, misure di sicurezza, contratti, procedure...')
                                ->columnSpanFull(),

                            Textarea::make('summary')
                                ->label('Sintesi dell\'audit')
                                ->rows(4)
                                ->placeholder('Riepilogo generale degli esiti...')
                                ->columnSpanFull(),

                            Textarea::make('auditor_notes')
                                ->label('Note dell\'auditor')
                                ->rows(3)
                                ->columnSpanFull(),
                        ]),
                    ]),

            ])->columnSpanFull(),
        ]);
    }
}
