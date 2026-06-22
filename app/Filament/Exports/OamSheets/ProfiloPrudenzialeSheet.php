<?php

namespace App\Filament\Exports\OamSheets;

use App\Models\Audit;
use App\Models\AuditFinding;
use App\Models\Company;
use App\Models\CompanyIspection;
use App\Models\ComplaintRegistry;
use App\Models\SuspiciousActivityReport;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ProfiloPrudenzialeSheet implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    public function __construct(
        private readonly Company $company,
        private readonly Carbon $startAt,
        private readonly Carbon $endAt,
        private readonly string $periodoLabel,
    ) {}

    public function title(): string
    {
        return 'PROFILO PRUDENZIALE';
    }

    public function array(): array
    {
        $companyId = $this->company->id;

        $ispezioniProgrammate = CompanyIspection::where('company_id', $companyId)
            ->whereBetween('dal', [$this->startAt, $this->endAt])
            ->count();

        $ispezioniEffettuate = CompanyIspection::where('company_id', $companyId)
            ->whereBetween('dal', [$this->startAt, $this->endAt])
            ->whereNotNull('al')
            ->count();

        $auditProgrammati = Audit::where('company_id', $companyId)
            ->whereBetween('planned_at', [$this->startAt, $this->endAt])
            ->count();

        $auditEseguiti = Audit::where('company_id', $companyId)
            ->whereBetween('completed_at', [$this->startAt, $this->endAt])
            ->count();

        $sosTotali = SuspiciousActivityReport::whereBetween('reported_at', [$this->startAt, $this->endAt])
            ->count();

        $reclamiRicevuti = ComplaintRegistry::where('company_id', $companyId)
            ->whereBetween('received_at', [$this->startAt, $this->endAt])
            ->count();

        $rilievi = AuditFinding::where('company_id', $companyId)
            ->with('audit')
            ->whereBetween('created_at', [$this->startAt, $this->endAt])
            ->get();

        $rows = [
            ['SEZIONE C — PROFILO PRUDENZIALE'],
            ['Periodo: '.$this->periodoLabel],
            [],
            ['MPP1', 'Numero accessi ispettivi programmati', $ispezioniProgrammate],
            ['MPP2', 'Numero accessi ispettivi effettuati', $ispezioniEffettuate],
            ['MPP3', 'Numero audit programmati', $auditProgrammati],
            ['MPP4', 'Numero audit eseguiti', $auditEseguiti],
            ['MPP5', 'Numero SOS effettuate (antiriciclaggio)', $sosTotali],
            ['MPP6', 'Numero reclami ricevuti', $reclamiRicevuti],
            [],
            ['RILIEVI E AZIONI DI RIMEDIO'],
            [],
            [
                'Codice',
                'Audit di riferimento',
                'Data rilievo',
                'Gravità',
                'Rilievo riscontrato',
                'Azione di rimedio richiesta',
                'Scadenza rimedio',
                'Stato',
            ],
        ];

        foreach ($rilievi as $i => $finding) {
            $rows[] = [
                'MPP'.(7 + $i),
                $finding->audit?->name ?? '—',
                $finding->created_at?->format('d/m/Y') ?? '—',
                $finding->severity?->label() ?? $finding->severity ?? '—',
                $finding->title,
                $finding->corrective_action_description ?? '—',
                $finding->corrective_action_deadline?->format('d/m/Y') ?? '—',
                $finding->status?->label() ?? $finding->status ?? '—',
            ];
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $highestCol = $sheet->getHighestColumn();

                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
                $sheet->getStyle('A1:'.$highestCol.'1')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FF1F497D');
                $sheet->getStyle('A1')->getFont()->getColor()->setARGB('FFFFFFFF');

                // Intestazioni sezione rilievi (riga 13)
                $sheet->getStyle('A13:'.$highestCol.'13')->getFont()->setBold(true);
                $sheet->getStyle('A13:'.$highestCol.'13')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFD9E1F2');

                // Etichette contatori in grassetto
                $sheet->getStyle('A4:B9')->getFont()->setBold(true);
            },
        ];
    }
}
