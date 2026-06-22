<?php

namespace App\Filament\Exports;

use App\Filament\Exports\OamSheets\AnagraficaSheet;
use App\Filament\Exports\OamSheets\ProfiloEconomicoSheet;
use App\Filament\Exports\OamSheets\ProfiloInformativoSheet;
use App\Filament\Exports\OamSheets\ProfiloPrudenzialeSheet;
use App\Filament\Exports\OamSheets\SediTerritorialiSheet;
use App\Models\Company;
use App\Services\CompanyResolver;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class OamSemestraleExport implements WithMultipleSheets
{
    private readonly Company $company;

    private readonly Carbon $startAt;

    private readonly Carbon $endAt;

    private readonly string $period;

    private readonly string $periodoLabel;

    private readonly string $year;

    private readonly int $semestre;

    public function __construct(int $anno, int $semestre)
    {
        $this->company = app(CompanyResolver::class)->resolve();
        $this->year = (string) $anno;
        $this->semestre = $semestre;

        if ($semestre === 1) {
            $this->startAt = Carbon::create($anno, 1, 1)->startOfDay();
            $this->endAt = Carbon::create($anno, 6, 30)->endOfDay();
            $this->period = $anno.'01';
        } else {
            $this->startAt = Carbon::create($anno, 7, 1)->startOfDay();
            $this->endAt = Carbon::create($anno, 12, 31)->endOfDay();
            $this->period = $anno.'07';
        }

        $this->periodoLabel = sprintf(
            '%s – %s',
            $this->startAt->format('d/m/Y'),
            $this->endAt->format('d/m/Y'),
        );
    }

    public function sheets(): array
    {
        return [
            new AnagraficaSheet(
                company: $this->company,
                periodoLabel: $this->periodoLabel,
                year: $this->year,
                semestre: $this->semestre,
            ),
            new ProfiloEconomicoSheet(
                company: $this->company,
                period: $this->period,
                periodoLabel: $this->periodoLabel,
            ),
            new ProfiloPrudenzialeSheet(
                company: $this->company,
                startAt: $this->startAt,
                endAt: $this->endAt,
                periodoLabel: $this->periodoLabel,
            ),
            new ProfiloInformativoSheet(
                company: $this->company,
                periodoLabel: $this->periodoLabel,
            ),
            new SediTerritorialiSheet(
                company: $this->company,
                periodoLabel: $this->periodoLabel,
            ),
        ];
    }
}
