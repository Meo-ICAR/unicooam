<?php

namespace App\Filament\Resources\OamSemestrales\Pages;

use App\Filament\Actions\ExportOamAction;
use App\Filament\Actions\ImportOamAction;
use App\Filament\Exports\Sheets\M510AnagraficaSheet;
use App\Filament\Exports\Sheets\M510EconomicoBaseSheet;
use App\Filament\Exports\Sheets\M510InformativoSheet;
use App\Filament\Exports\Sheets\M510PrudenzialeSheet;
use App\Filament\Exports\Sheets\M510SediSheet;
use App\Filament\Exports\M510MasterExport;
use App\Filament\Resources\OamSemestrales\OamSemestraleResource;
use App\Models\PROFORMA\Fornitore;
use App\Models\Audit;
use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanyRole;
use App\Models\ComplaintRegistry;
use App\Models\Document;
use App\Models\Employee;
use App\Models\OamSemestrale;
use App\Models\SuspiciousActivityReport;
use App\Models\Website;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;  // CORRETTO
use Illuminate\Support\HtmlString;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListOamSemestrales extends ListRecords
{
    protected static string $resource = OamSemestraleResource::class;

    protected function getHeaderActions(): array
    {
        $oams = OamSemestrale::all();
        $datiProdotti = $oams->toArray();
        $provvigioni_assicurative = 0;  // $oams->sum('provvigioni_assicurative');

        $azienda = Company::first();
        $sedi = Branch::where('company_id', $azienda->id)->get();

        $dipendenti = Employee::where('company_id', $azienda->id)
            ->where('is_active', true);
        $fornitori = Fornitore::where('is_active', true);
        $reclami = ComplaintRegistry::count();  // $oams->sum('reclami');
        $sars = SuspiciousActivityReport::count();

        $compliance_doc = CompanyRole::where('funzione', '=', 'compliance')->where('execution_method', '=', 'documentale')->count();
        $compliance_onsite = CompanyRole::where('funzione', '=', 'compliance')->where('execution_method', '=', 'onsite')->count();
        $externalRoles = CompanyRole::where('is_external', true)->distinct('funzione')->get();

        $audit_doc = Audit::where('company_id', $azienda->id)->count();
        $audit_onsite = Audit::where('company_id', $azienda->id)->count();

        $websites = Website::where('is_active', true);
        $website_trasparenza = $websites->where('type', 'istituzionale')->first()->transparency_date;

        $procedures = Document::where('documentable_type', 'company')
            ->where('doctype', '=', 'procedura')
            ->orderBy('emitted_at', 'desc')
            ->get()
            ->map(function ($doc) {
                $data = $doc->emitted_at ? date('d/m/Y', strtotime($doc->emitted_at)) : 'N/D';
                return "[{$data}] " . ($doc->name ?? $doc->title);
            })
            ->toArray();
        $requisiti_organizzativi = Document::where('documentable_type', 'company')->whereHas('documentType', function ($query) {
            $query->where('slug', 'requisiti-organizzativi');
        })->orderBy('emitted_at', 'desc')->first();
        $moduli = Document::where('documentable_type', 'company')->where('doctype', '=', 'modulo')->orderBy('emitted_at', 'desc')->get();

        return [
            ImportOamAction::make()
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('info'),
            Action::make('debugAnagrafica')
                ->label('Anagrafica')
                ->icon('heroicon-o-table-cells')
                ->color('success')  // Colore verde per indicare che è uno strumento di debug
                ->action(function () use ($azienda, $dipendenti, $fornitori): BinaryFileResponse {
                    // 1. Recuperiamo la prima azienda disponibile nel database per i dati reali

                    // 2. Prepariamo il set di dati richiesto dal costruttore di M510AnagraficaSheet
                    $datiTest = [
                        'ragione_sociale' => $azienda->name ?? 'AZIENDA DI TEST SPA',
                        'piva' => $azienda->vat_number ?? '01234567890',
                        'periodo' => '01.01.2026 - 30.06.2026',
                        'num_dipendenti' => $dipendenti->where('employee_types', 'dipendente')->count(),
                        'num_collaboratori' => $fornitori->count(),
                        'sedi_territoriali' => $azienda->branches->count(),
                        'progressivo' => '1/2026',
                        'profilo_analitico' => 'NO',
                        'profilo_base' => 'COMPILAZIONE OBBLIGATORIA',
                    ];

                    // 3. Eseguiamo il download diretto del singolo foglio isolato
                    return Excel::download(
                        new M510AnagraficaSheet($datiTest),
                        'DEBUG_M510_Anagrafica.xlsx'
                    );
                }),
            Action::make('debugEconomicoBase')
                ->label('1-Economico')
                ->icon('heroicon-o-table-cells')
                ->color('secondary')
                ->action(function () use ($datiProdotti): BinaryFileResponse {
                    return Excel::download(
                        new M510EconomicoBaseSheet($datiProdotti),
                        'DEBUG_M510_Economico_Base.xlsx'
                    );
                }),
            Action::make('debugPrudenziale')
                ->label('3-Prudenziale')
                ->icon('heroicon-o-table-cells')
                ->color('warning')  // Colore arancione per distinguerlo
                ->action(function () use ($azienda, $provvigioni_assicurative, $reclami, $sars, $compliance_onsite, $audit_onsite, $compliance_doc, $audit_doc): BinaryFileResponse {
                    $auditsRegistrati = Audit::get();

                    $rilievi_lista = [];

                    foreach ($auditsRegistrati as $audit) {
                        // Risolviamo il nome del soggetto controllato in base all'auditable_type
                        $nomeSoggetto = 'Soggetto sconosciuto';

                        if ($audit->auditable_type === 'employee') {
                            // Esegui la query sulla tabella degli impiegati/collaboratori
                            $nomeSoggetto = Employee::where('id', $audit->auditable_id)->value('name');
                        } elseif ($audit->auditable_type === 'fornitore') {
                            // Esegui la query sulla tabella dei fornitori
                            $nomeSoggetto = Fornitore::where('id', $audit->auditable_id)->value('name');
                        }

                        // Inseriamo i dati reali convertendo i null del tuo database in stringhe leggibili per l'Excel
                        $rilievi_lista[] = [
                            'collaboratore' => $nomeSoggetto,
                            'executed_at' => $audit->executed_at ? date('d/m/Y', strtotime($audit->executed_at)) : '',
                            'auditor_name' => $audit->auditor_name ?? 'Non Assegnato',
                            'summary' => $audit->summary ?? 'Nessun rilievo bloccante riscontrato (Stato: ' . $audit->status->value . ')',
                            'remediation_plan' => $audit->remediation_plan ?? 'Nessuna azione richiesta',
                            'followup_date' => $audit->followup_date ? date('d/m/Y', strtotime($audit->followup_date)) : 'N/A',
                        ];
                    }

                    $datiTestPrudenziale = [
                        'gruppo' => $azienda->sponsor,
                        'provvigioni' => $provvigioni_assicurative,
                        'reclami' => $reclami,
                        'sos' => $sars,
                        'ispezioni_prog' => $compliance_onsite,
                        'ispezioni_eff' => $audit_onsite,
                        'audit_prog' => $compliance_doc,
                        'audit_eff' => $audit_doc,
                        'rilievi_lista' => $rilievi_lista,
                    ];

                    // 2. Scarichiamo esclusivamente il singolo foglio Prudenziale
                    return Excel::download(
                        new M510PrudenzialeSheet($datiTestPrudenziale),
                        'DEBUG_M510_Prudenziale.xlsx'
                    );
                }),
            Action::make('debugInformativo')
                ->label('4-Informativo')
                ->icon('heroicon-o-table-cells')
                ->color('info')
                ->action(function () use ($websites, $website_trasparenza, $requisiti_organizzativi, $procedures, $moduli, $externalRoles): BinaryFileResponse {
                    // Struttura dati fittizia ricavata dall'immagine

                    $datiTestInformativo = [
                        'numero_siti' => $websites->count(),
                        'siti' => $websites->pluck('domain')->toArray(),
                        'data_trasparenza' => $website_trasparenza,
                        'data_relazione_requisiti' => $requisiti_organizzativi ? date('d/m/Y', strtotime($requisiti_organizzativi->emitted_at)) : 'N/A',
                        'procedure' => $procedures,
                        'moduli' => $moduli->pluck('name')->toArray(),
                        'modulistica_a' => $moduli->pluck('emitted_at')->map(function ($date) {
                            return date('d/m/Y', strtotime($date));
                        })->toArray(),
                        'funzioni_esternalizzate' => $externalRoles->pluck('funzione')->unique()->values()->toArray(),
                    ];

                    return Excel::download(
                        new M510InformativoSheet($datiTestInformativo),
                        'DEBUG_M510_Informativo.xlsx'
                    );
                }),
            Action::make('debugSedi')
                ->label('5-Sedi')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->action(function () use ($azienda): BinaryFileResponse {
                    $branches_lista = [];
                    $filialiRegistrate = Branch::get();

                    foreach ($filialiRegistrate as $branch) {
                        // Il Mockup richiede il formato "COGNOME NOME"
                        $responsabile = 'N/D';
                        if ($branch->manager_last_name || $branch->manager_first_name) {
                            $responsabile = trim(($branch->manager_last_name ?? '') . ' ' . ($branch->manager_first_name ?? ''));
                        }

                        $branches_lista[] = [
                            'address' => $branch->address,
                            'street_number' => $branch->street_number,
                            'city' => $branch->city,
                            'zip_code' => $branch->zip_code,
                            'province' => $branch->province,
                            'region' => $branch->region,
                            'responsabile' => $responsabile,
                            'is_main_office' => $branch->is_main_office ? 'SI' : 'NO',  // Trasformato in SI/NO secco per la colonna I
                        ];
                    }
                    $datiExport = [
                        'gruppo' => $azienda->sponsor ?? 'RACES FINANZIARIA SPA',
                        'branches_lista' => $branches_lista,
                    ];

                    // Richiamo esplicito al nuovo M510SediSheet
                    return Excel::download(
                        new M510SediSheet($datiExport),
                        'DEBUG_M510_Sedi_Operative.xlsx'
                    );
                }),
        ];
    }
}
