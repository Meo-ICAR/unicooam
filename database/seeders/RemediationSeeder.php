<?php

namespace Database\Seeders;

use App\Models\Remediation;
use Illuminate\Database\Seeder;

class RemediationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $remediations = [
            [
                'id' => 1,
                'remediation_type' => 'AML',
                'name' => 'Segnalazione Operazione Sospetta (SOS)',
                'code' => null,
                'description' => 'Predisposizione e invio immediato della SOS alla UIF...',
                'timeframe_hours' => 24,
                'timeframe_desc' => 'Immediato (max 24 ore)',
                'created_at' => '2026-03-18 10:19:00',
                'updated_at' => '2026-03-18 10:19:00',
            ],
            [
                'id' => 2,
                'remediation_type' => 'Monitoraggio Rete',
                'name' => 'Sospensione cautelare collaboratore',
                'code' => null,
                'description' => 'Blocco immediato delle credenziali di accesso al gestionale...',
                'timeframe_hours' => 48,
                'timeframe_desc' => 'Entro 48 ore',
                'created_at' => '2026-03-18 10:19:00',
                'updated_at' => '2026-03-18 10:19:00',
            ],
            [
                'id' => 3,
                'remediation_type' => 'Privacy',
                'name' => 'Notifica Data Breach',
                'code' => null,
                'description' => 'Raccolta delle informazioni sulla violazione dei dati...',
                'timeframe_hours' => 72,
                'timeframe_desc' => 'Entro 72 ore',
                'created_at' => '2026-03-18 10:19:00',
                'updated_at' => '2026-03-18 10:19:00',
            ],
            [
                'id' => 4,
                'remediation_type' => 'AML',
                'name' => 'Integrazione documentazione per Adeguata Verifica',
                'code' => null,
                'description' => 'Contatto con il cliente per richiedere documenti mancanti...',
                'timeframe_hours' => 168,
                'timeframe_desc' => 'Entro 7 giorni',
                'created_at' => '2026-03-18 10:19:00',
                'updated_at' => '2026-03-18 10:19:00',
            ],
            [
                'id' => 5,
                'remediation_type' => 'Gestione Reclami',
                'name' => 'Risoluzione e riscontro reclamo',
                'code' => null,
                'description' => 'Redazione formale della lettera di risposta al reclamo...',
                'timeframe_hours' => 168,
                'timeframe_desc' => 'Entro 7 giorni',
                'created_at' => '2026-03-18 10:19:00',
                'updated_at' => '2026-03-18 10:19:00',
            ],
            [
                'id' => 6,
                'remediation_type' => 'Monitoraggio Rete',
                'name' => 'Regolarizzazione formazione obbligatoria',
                'code' => null,
                'description' => "Sollecito e iscrizione d'ufficio dei collaboratori ai corsi...",
                'timeframe_hours' => 720,
                'timeframe_desc' => 'Entro 30 giorni',
                'created_at' => '2026-03-18 10:19:00',
                'updated_at' => '2026-03-18 10:19:00',
            ],
            [
                'id' => 7,
                'remediation_type' => 'Assetto Organizzativo',
                'name' => 'Aggiornamento Manuale Operativo',
                'code' => null,
                'description' => 'Revisione del manuale e del sistema di deleghe...',
                'timeframe_hours' => 1440,
                'timeframe_desc' => 'Entro 60 giorni',
                'created_at' => '2026-03-18 10:19:00',
                'updated_at' => '2026-03-18 10:19:00',
            ],
        ];

        foreach ($remediations as $remediation) {
            Remediation::create($remediation);
        }

        $this->command->info(count($remediations) . ' remediation records created.');
    }
}
