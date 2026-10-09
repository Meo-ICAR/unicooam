<?php

namespace App\Filament\Resources\QualityReviews\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\QualityReviews\QualityReviewResource;
use App\Models\Company;
use App\Models\PROFORMA\Pratica;
use App\Models\User;
use App\Services\QualityReviewSampler;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

class ListQualityReviews extends ListRecords
{
    protected static string $resource = QualityReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('estraiCampione')
                ->label('Estrai campione')
                ->icon(Heroicon::Funnel)
                ->modalHeading('Estrai un campione casuale di pratiche')
                ->modalSubmitActionLabel('Crea controllo')
                ->fillForm(fn (): array => [
                    'name' => 'Controllo qualità '.now()->format('m/Y'),
                    'period_from' => now()->subMonths(3)->toDateString(),
                    'period_to' => now()->toDateString(),
                    'sample_size' => 10,
                    'escludi_gia_campionate' => true,
                    'reviewer_user_id' => User::query()->where('role', UserRole::QUALITY->value)->value('id'),
                ])
                ->schema([
                    TextInput::make('name')->label('Nome del controllo')->required()->columnSpanFull(),
                    Grid::make(3)->schema([
                        DatePicker::make('period_from')->label('Inserite dal')->displayFormat('d/m/y')->native(false)->live(),
                        DatePicker::make('period_to')->label('Inserite fino al')->displayFormat('d/m/y')->native(false)->live(),
                        TextInput::make('sample_size')->label('N. pratiche da estrarre')->numeric()->minValue(1)->maxValue(500)->required(),
                    ]),
                    Select::make('stati')->label('Stato pratica')->multiple()->searchable()->live()
                        ->options(fn () => self::distinctOptions('stato_pratica')),
                    Select::make('tipi_prodotto')->label('Tipo prodotto')->multiple()->searchable()->live()
                        ->options(fn () => self::distinctOptions('tipo_prodotto')),
                    Select::make('banche')->label('Banca / Istituto')->multiple()->searchable()->live()
                        ->options(fn () => self::distinctOptions('denominazione_banca')),
                    Select::make('agenti')->label('Agente / Produttore')->multiple()->searchable()->live()
                        ->options(fn () => Pratica::query()
                            ->whereNotNull('partita_iva_agente')->whereNotNull('denominazione_agente')
                            ->distinct()->orderBy('denominazione_agente')
                            ->pluck('denominazione_agente', 'partita_iva_agente')->all()),
                    Grid::make(2)->schema([
                        TextInput::make('erogato_min')->label('Erogato minimo')->numeric()->live(onBlur: true),
                        TextInput::make('erogato_max')->label('Erogato massimo')->numeric()->live(onBlur: true),
                    ]),
                    Toggle::make('escludi_gia_campionate')->label('Escludi pratiche già controllate in precedenza')->live(),
                    Select::make('reviewer_user_id')->label('Responsabile controllo qualità')
                        ->options(fn () => User::query()->where('role', UserRole::QUALITY->value)->pluck('name', 'id'))
                        ->searchable(),
                    TextInput::make('candidati')
                        ->label('Pratiche disponibili con questi filtri')
                        ->disabled()->dehydrated(false)
                        ->placeholder(fn (Get $get): string => (string) app(QualityReviewSampler::class)->countCandidates(self::filtersFrom($get))),
                ])
                ->action(function (array $data): void {
                    $filters = self::filtersFrom(fn (string $key) => $data[$key] ?? null);

                    $review = app(QualityReviewSampler::class)->createReview(
                        name: $data['name'],
                        sampleSize: (int) $data['sample_size'],
                        filters: $filters,
                        reviewerUserId: $data['reviewer_user_id'] ?? null,
                        companyId: Company::first()?->id,
                    );

                    Notification::make()
                        ->title("Estratte {$review->sample_size} pratiche su {$data['sample_size']} richieste")
                        ->success()
                        ->send();

                    $this->redirect(QualityReviewResource::getUrl('edit', ['record' => $review]));
                }),
        ];
    }

    /**
     * @return array<int|string, string>
     */
    private static function distinctOptions(string $column): array
    {
        return Pratica::query()->whereNotNull($column)->where($column, '!=', '')
            ->distinct()->orderBy($column)->pluck($column, $column)->all();
    }

    /**
     * @param  callable(string): mixed  $get
     * @return array<string, mixed>
     */
    private static function filtersFrom(callable $get): array
    {
        return array_filter([
            'period_from' => $get('period_from'),
            'period_to' => $get('period_to'),
            'stati' => $get('stati'),
            'tipi_prodotto' => $get('tipi_prodotto'),
            'banche' => $get('banche'),
            'agenti' => $get('agenti'),
            'erogato_min' => $get('erogato_min'),
            'erogato_max' => $get('erogato_max'),
        ], fn ($v) => filled($v)) + ['escludi_gia_campionate' => (bool) $get('escludi_gia_campionate')];
    }
}
