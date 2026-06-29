<?php

namespace App\Filament\Exports;

use App\Exports\Sheets\M510AnagraficaSheet;
use App\Exports\Sheets\M510PrudenzialeSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
// Importa i tuoi modelli reali del database
use App\Models\Audit;
use App\Models\Company;
use App\Models\Employee;

class OamSemestraleExport implements WithMultipleSheets
{
    use Exportable;

    protected int $anno;
    protected int $semestre;

    public function __construct(int $anno, int $semestre)
    {
        $this->anno = $anno;
        $this->semestre = $semestre;
    }

    public function sheets(): array
    {
        // 1. Calcolo del range di date in base al semestre selezionato
        $startDate = $this->semestre === 1 ? "{$this->anno}-01-01" : "{$this->anno}-07-01";
        $endDate = $this->semestre === 1 ? "{$this->anno}-06-30" : "{$this->anno}-12-31";

        $periodoTesto = $this->semestre === 1
            ? "01.01.{$this->anno} - 30.06.{$this->anno}"
            : "01.07.{$this->anno} - 31.12.{$this->anno}";

        // 2. Query sul database (estrazione dei dati dalle varie tabelle filtrando per data)
        $azienda = Company::first();  // Sostituisci con la tua logica di selezione azienda

        $numDipendenti = Employee::where('company_id', $azienda?->id)
            ->where('tipo', 'dipendente')
            ->count();

        $numCollaboratori = Employee::where('company_id', $azienda?->id)
            ->where('tipo', 'collaboratore')
            ->count();

        // Estrazione record per la tabella MPP9 del foglio Prudenziale
        $rilieviDb = Audit::where('company_id', $azienda?->id)
            ->whereBetween('data_audit', [$startDate, $endDate])
            ->get();

        $rilieviLista = [];
        foreach ($rilieviDb as $r) {
            $rilieviLista[] = [
                'collaboratore' => $r->collaboratore_nominativo,
                'data' => $r->data_audit?->format('d/m/Y') ?? '',
                'ispettore' => $r->ispettore_nominativo,
                'rilievo' => $r->descrizione_rilievo,
                'rimedio' => $r->azioni_correttive,
                'termine' => $r->scadenza_rimedio?->format('d/m/Y') ?? '',
            ];
        }

        // 3. Preparazione dei dataset strutturati per i singoli fogli
        $datiAnagrafica = [
            'ragione_sociale' => $azienda->ragione_sociale ?? 'RACES FINANCE SRL',
            'piva' => $azienda->partita_iva ?? '10282211001',
            'periodo' => $periodoTesto,
            'num_dipendenti' => $numDipendenti,
            'num_collaboratori' => $numCollaboratori,
            'sedi_territoriali' => '',
            'progressivo' => "{$this->semestre}/{$this->anno}",
            'profilo_analitico' => 'NO',
            'profilo_base' => "E' OBBLIGATORIO COMPILARE IL PROFILO ECONOMICO OPERATIVO BASE",
        ];

        $datiPrudenziale = [
            'gruppo' => $azienda->nome_gruppo ?? 'NESSUN GRUPPO',
            'provvigioni' => '',
            'reclami' => 0,
            'sos' => 0,
            'ispezioni_prog' => 0,
            'ispezioni_eff' => 0,
            'audit_prog' => 0,
            'audit_eff' => 0,
            'rilievi_lista' => $rilieviLista,
        ];

        // 4. Ritorno dei 5 fogli richiesti dalla segnalazione OAM
        return [
            new M510AnagraficaSheet($datiAnagrafica),
            new M510PrudenzialeSheet($datiPrudenziale),
            // Placeholder temporanei per gli altri 3 fogli per non far fallire l'esportazione a 5 tab
            new class implements \Maatwebsite\Excel\Concerns\WithTitle {
                public function title(): string
                {
                    return 'Profilo Economico Base';
                }
            },
            new class implements \Maatwebsite\Excel\Concerns\WithTitle {
                public function title(): string
                {
                    return 'Trasparenza';
                }
            },
            new class implements \Maatwebsite\Excel\Concerns\WithTitle {
                public function title(): string
                {
                    return 'Antiriciclaggio';
                }
            },
        ];
    }
}
