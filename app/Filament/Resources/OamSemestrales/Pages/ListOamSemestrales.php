<?php

namespace App\Filament\Resources\OamSemestrales\Pages;

use App\Filament\Actions\ImportOamAction;
use App\Filament\Exports\Sheets\M510AnagraficaSheet;
use App\Filament\Exports\Sheets\M510EconomicoBaseSheet;
use App\Filament\Exports\Sheets\M510InformativoSheet;
use App\Filament\Exports\Sheets\M510PrudenzialeSheet;
use App\Filament\Exports\Sheets\M510SediSheet;
use App\Filament\Exports\Sheets\OamCompletoExport; // Importa la nuova classe master
use App\Filament\Resources\OamSemestrales\OamSemestraleResource;
use App\Models\Audit;
use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanyRole;
use App\Models\ComplaintRegistry;
use App\Models\Document;
use App\Models\Employee;
use App\Models\OamSemestrale;
use App\Models\PROFORMA\Fornitore;
use App\Models\SuspiciousActivityReport;
use App\Models\Website;
use App\ValueObjects\OamSemester;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
// CORRETTO
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListOamSemestrales extends ListRecords
{
    protected static string $resource = OamSemestraleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportOamAction::make()
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info'),

            // --- EXCEL GLOBALE MULTI-SHEET ---
            Action::make('export_completo')
                ->label('Completo')
                ->icon('heroicon-o-document-duplicate')
                ->color('success')
                ->visible(fn (Action $action) => checkPiano($action->getName()))
                ->action(fn (): BinaryFileResponse => Excel::download(
                    new OamCompletoExport(
                        $this->getDatiAnagrafica(),
                        $this->getDatiEconomici(),
                        $this->getDatiInformativo(),
                        $this->getDatiSedi(),
                        $this->getDatiPrudenziale()
                    ),
                    'OAM_Completo.xlsx'
                )),

            // --- SINGOLI FOGLI ISOLATI ---
            Action::make('Analitico')
                ->label('Analitico')
                ->icon('heroicon-o-chart-bar')
                ->color('info')
                ->action(fn (): BinaryFileResponse => Excel::download(
                    new M510EconomicoBaseSheet($this->getDatiEconomici()),
                    'OAM_Analitico.xlsx'
                )),

            Action::make('Anagrafica')
                ->label('Anagrafica')
                ->visible(fn (Action $action) => checkPiano($action->getName()))
                ->icon('heroicon-o-identification')
                ->color('success')
                ->action(fn (): BinaryFileResponse => Excel::download(
                    new M510AnagraficaSheet($this->getDatiAnagrafica()),
                    'OAM_Anagrafica.xlsx'
                )),

            Action::make('Informativo')
                ->visible(fn (Action $action) => checkPiano($action->getName()))
                ->label('Informativo')
                ->icon('heroicon-o-information-circle')
                ->color('success')
                ->action(fn (): BinaryFileResponse => Excel::download(
                    new M510InformativoSheet($this->getDatiInformativo()),
                    'OAM_Informativo.xlsx'
                )),

            Action::make('Sedi')
                ->visible(fn (Action $action) => checkPiano($action->getName()))
                ->label('Sedi')
                ->icon('heroicon-o-map-pin')
                ->color('success')
                ->action(fn (): BinaryFileResponse => Excel::download(
                    new M510SediSheet($this->getDatiSedi()),
                    'OAM_Sedi_Operative.xlsx'
                )),

            Action::make('Prudenziale')
                ->label('Prudenziale')
                ->visible(fn (Action $action) => checkPiano($action->getName()))
                ->icon('heroicon-o-shield-check')
                ->color('warning')
                ->action(fn (): BinaryFileResponse => Excel::download(
                    new M510PrudenzialeSheet($this->getDatiPrudenziale()),
                    'OAM_Prudenziale.xlsx'
                )),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | METODI GETTER DI PREPARAZIONE DATI (Zero Duplicazione)
    |--------------------------------------------------------------------------
    */

    protected function getDatiAnagrafica(): array
    {
        $semestre = OamSemester::getInBaseAlMeseCorrente();
        $azienda = Company::first();
        $dipendenti = Employee::perSemestreOam($semestre)->Employee()->where('company_id', $azienda?->id);
        $fornitori = Fornitore::perSemestreOam($semestre);

        return [
            'ragione_sociale' => $azienda->name ?? 'AZIENDA DI TEST SPA',
            'piva' => $azienda->vat_number ?? '01234567890',
            'periodo' => '01.01.2026 - 30.06.2026',
            'num_dipendenti' => $dipendenti->count(),
            'num_collaboratori' => $fornitori->count(),
            'sedi_territoriali' => $azienda?->branches()->count() ?? 0,
            'progressivo' => '1/2026',
            'profilo_analitico' => 'COMPILAZIONE OBBLIGATORIA',
            'profilo_base' => 'NO',
        ];
    }

    protected function getDatiEconomici(): array
    {
        return OamSemestrale::all()->toArray();
    }

    protected function getDatiInformativo(): array
    {
        $semestre = OamSemester::getInBaseAlMeseCorrente();
        $websites = Website::where('is_active', true);
        $website_trasparenza = $websites->where('type', 'istituzionale')->first()?->transparency_date;

        $procedures = Document::perSemestreOam($semestre)
            ->where('documentable_type', 'company')
            ->where('doctype', '=', 'procedura')
            ->orderBy('emitted_at', 'desc')
            ->get()
            ->map(function ($doc) {
                $data = $doc->emitted_at ? date('d/m/y', strtotime($doc->emitted_at)) : 'N/D';

                return "[{$data}] ".($doc->name ?? $doc->title);
            })
            ->toArray();

        $requisiti_organizzativi = Document::perSemestreOam($semestre)
            ->where('documentable_type', 'company')
            ->whereHas('documentType', fn ($query) => $query->where('slug', 'requisiti-organizzativi'))
            ->orderBy('emitted_at', 'desc')
            ->first();

        $moduli = Document::perSemestreOam($semestre)
            ->where('documentable_type', 'company')
            ->where('doctype', '=', 'modulo')
            ->orderBy('emitted_at', 'desc')
            ->get();

        $externalRoles = CompanyRole::where('is_external', true)->distinct('funzione')->get();

        return [
            'numero_siti' => $websites->count(),
            'siti' => $websites->pluck('domain')->toArray(),
            'data_trasparenza' => $website_trasparenza,
            'data_relazione_requisiti' => $requisiti_organizzativi ? date('d/m/y', strtotime($requisiti_organizzativi->emitted_at)) : 'N/A',
            'procedure' => $procedures,
            'moduli' => $moduli->pluck('name')->toArray(),
            'modulistica_a' => $moduli->pluck('emitted_at')->map(fn ($date) => date('d/m/y', strtotime($date)))->toArray(),
            'funzioni_esternalizzate' => $externalRoles->pluck('funzione')->unique()->values()->toArray(),
        ];
    }

    protected function getDatiSedi(): array
    {
        $azienda = Company::first();
        $filialiRegistrate = Branch::get();
        $branches_lista = [];

        foreach ($filialiRegistrate as $branch) {
            $responsabile = 'N/D';
            if ($branch->manager_last_name || $branch->manager_first_name) {
                $responsabile = trim(($branch->manager_last_name ?? '').' '.($branch->manager_first_name ?? ''));
            }

            $branches_lista[] = [
                'address' => $branch->address,
                'street_number' => $branch->street_number,
                'city' => $branch->city,
                'zip_code' => $branch->zip_code,
                'province' => $branch->province,
                'region' => $branch->region,
                'responsabile' => $responsabile,
                'is_main_office' => $branch->is_main_office ? 'SI' : 'NO',
            ];
        }

        return [
            'gruppo' => $azienda->sponsor ?? 'RACES FINANZIARIA SPA',
            'branches_lista' => $branches_lista,
        ];
    }

    protected function getDatiPrudenziale(): array
    {
        $semestre = OamSemester::getInBaseAlMeseCorrente();
        $azienda = Company::first();

        $provvigioni_assicurative = 0;
        $reclami = ComplaintRegistry::perSemestreOam($semestre)->count();
        $sars = SuspiciousActivityReport::perSemestreOam($semestre)->count();

        $compliance_doc = CompanyRole::where('funzione', '=', 'compliance')->where('execution_method', '=', 'documentale')->count();
        $compliance_onsite = CompanyRole::where('funzione', '=', 'compliance')->where('execution_method', '=', 'onsite')->count();

        $audit_doc = Audit::perSemestreOam($semestre)->where('company_id', $azienda?->id)->count();
        $audit_onsite = Audit::perSemestreOam($semestre)->where('company_id', $azienda?->id)->count();

        $auditsRegistrati = Audit::get();
        $rilievi_lista = [];

        foreach ($auditsRegistrati as $audit) {
            $nomeSoggetto = 'Soggetto sconosciuto';

            if ($audit->auditable_type === 'employee') {
                $nomeSoggetto = Employee::where('id', $audit->auditable_id)->value('name');
            } elseif ($audit->auditable_type === 'fornitore') {
                $nomeSoggetto = Fornitore::where('id', $audit->auditable_id)->value('name');
            }

            $rilievi_lista[] = [
                'collaboratore' => $nomeSoggetto,
                'executed_at' => $audit->executed_at ? date('d/m/y', strtotime($audit->executed_at)) : '',
                'auditor_name' => $audit->auditor_name ?? 'Non Assegnato',
                'summary' => $audit->summary ?? 'Nessun rilievo bloccante riscontrato (Stato: '.$audit->status->value.')',
                'remediation_plan' => $audit->remediation_plan ?? 'Nessuna azione richiesta',
                'followup_date' => $audit->followup_date ? date('d/m/y', strtotime($audit->followup_date)) : 'N/A',
            ];
        }

        return [
            'gruppo' => $azienda?->sponsor,
            'provvigioni' => $provvigioni_assicurative,
            'reclami' => $reclami,
            'sos' => $sars,
            'ispezioni_prog' => $compliance_onsite,
            'ispezioni_eff' => $audit_onsite,
            'audit_prog' => $compliance_doc,
            'audit_eff' => $audit_doc,
            'rilievi_lista' => $rilievi_lista,
        ];
    }
}
