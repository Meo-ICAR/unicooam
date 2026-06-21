<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SuspiciousActivityReportSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = '3ff53405-4abb-4468-bff5-f9493badac5b';

        // reporter_type uses the BPM model FQN
        $agentType    = 'App\\Models\\BPM\\Agent';
        $employeeType = 'App\\Models\\BPM\\Employee';

        $reports = [
            [
                'company_id'    => $companyId,
                'client_id'     => 10,
                'reporter_type' => $agentType,
                'reporter_id'   => 1,
                'anomalies_codes' => json_encode(['1A', '4A']),
                'description'   => 'Il cliente ha effettuato tre operazioni di versamento in contanti in tre giorni consecutivi, ciascuna di importo appena inferiore alla soglia di €10.000. Profilo economico non coerente con le operazioni.',
                'status'        => 'reported',
                'reported_at'   => '2025-10-20 14:00:00',
            ],
            [
                'company_id'    => $companyId,
                'client_id'     => 11,
                'reporter_type' => $agentType,
                'reporter_id'   => 2,
                'anomalies_codes' => json_encode(['2B', '3A']),
                'description'   => 'Operazione di trasferimento fondi verso conto estero in paese a rischio senza giustificazione economica plausibile. Il cliente ha mostrato reticenza nel fornire documentazione di supporto.',
                'status'        => 'investigated',
                'reported_at'   => '2025-11-05 09:30:00',
            ],
            [
                'company_id'    => $companyId,
                'client_id'     => 12,
                'reporter_type' => $employeeType,
                'reporter_id'   => 3,
                'anomalies_codes' => json_encode(['5B']),
                'description'   => 'Rilevata anomalia nella frequenza di operazioni in contanti: 8 prelievi in 10 giorni per importi variabili tra €2.000 e €4.500. Comportamento inusuale rispetto alla storia operativa del cliente.',
                'status'        => 'pending',
                'reported_at'   => null,
            ],
            [
                'company_id'    => $companyId,
                'client_id'     => 13,
                'reporter_type' => $agentType,
                'reporter_id'   => 1,
                'anomalies_codes' => json_encode(['4B']),
                'description'   => 'Il nominativo del cliente risulta presente in una lista di soggetti sanzionati internazionali (OFAC). Operazione bloccata in attesa di verifica compliance.',
                'status'        => 'reported',
                'reported_at'   => '2025-12-01 11:00:00',
            ],
            [
                'company_id'    => $companyId,
                'client_id'     => 14,
                'reporter_type' => $employeeType,
                'reporter_id'   => 4,
                'anomalies_codes' => json_encode(['1B', '2A']),
                'description'   => 'Struttura operativa complessa con utilizzo di più conti intestati a soggetti collegati per frazionare importi elevati. Sospetto schema di layering.',
                'status'        => 'archived',
                'reported_at'   => '2025-08-15 16:00:00',
            ],
        ];

        foreach ($reports as $report) {
            DB::connection('mysql_compliance')->table('suspicious_activity_reports')->insert(array_merge($report, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
