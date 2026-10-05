<?php

namespace Database\Seeders;

use App\Models\PROFORMA\Fornitore;
use App\Models\TrainingRecord;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TrainingRecordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Recuperiamo dati reali per evitare crash di foreign key
        $companyId = DB::table('companies')->value('id') ?? (string) Str::uuid();

        // Per il polimorfismo stabiliamo che il corso sia frequentato da un Fornitore
        $fornitoreId = Fornitore::first()->id;
        $trainableType = 'App\Models\PROFORMA\Fornitore';
        $userId = User::first()->id;

        $records = [
            [
                'company_id' => $companyId,
                'trainable_type' => $trainableType,
                'trainable_id' => $userId,
                'regulatory_framework' => 'oam',
                'name' => 'Aggiornamento Professionale OAM 2026',
                'description' => 'Corso obbligatorio sulle nuove direttive di trasparenza bancaria.',
                'provider' => 'Accademia Finanziaria Italiana',
                'trainer' => 'Dott. Mario Rossi',
                'delivery_mode' => 'online',
                'training_date' => '2026-03-10',
                'expiry_date' => '2027-03-10',
                'hours' => 15.0,
                'outcome' => 'passed',
                'score' => 95.5,
                'certificate_issued' => true,
                'certificate_number' => 'CERT-OAM-2026-8839',
                'notes' => 'Test superato al primo tentativo con punteggio eccellente.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => $companyId,
                'trainable_type' => $trainableType,
                'trainable_id' => $userId,
                'regulatory_framework' => 'gdpr',
                'name' => 'Privacy e Sicurezza dei Dati in Azienda',
                'description' => 'Sensibilizzazione al trattamento dei dati sensibili della clientela.',
                'provider' => 'E-Learning Corporate',
                'trainer' => 'Ing. Silvia Bianchi',
                'delivery_mode' => 'webinar',
                'training_date' => '2026-05-22',
                'expiry_date' => null,  // Senza scadenza rigida
                'hours' => 4.5,
                'outcome' => 'attended',
                'score' => null,
                'certificate_issued' => false,
                'certificate_number' => null,
                'notes' => 'Attestato di sola frequenza richiesto dal reparto HR.',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        // Se le tabelle principali sono vuote disabilitiamo temporaneamente i controlli FK per sicurezza
        if (!DB::table('companies')->exists()) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            TrainingRecord::insert($records);
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } else {
            TrainingRecord::insert($records);
        }
    }
}
