<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('documents')->insert([
            'id' => Str::uuid()->toString(),
            'company_id' => '45d36df8-369f-40ce-b4fd-b5907c342fe9',
            'documentable_type' => 'App\Models\User',  // Modello associato
            'documentable_id' => Str::uuid()->toString(),  // Sostituisci con un UUID reale del target
            'document_type_id' => null,
            'name' => "Carta d'Identità Mario Rossi",
            'docnumber' => 'CA1234567',
            'spatie_collection' => 'identity_documents',
            'document_url' => 'https://storage.example.com/docs/ca1234567.pdf',
            'status' => 'verified',
            'sync_status' => 'synced',
            'source_app' => 'local',
            'metadata' => json_encode([
                'first_name' => 'Mario',
                'last_name' => 'Rossi',
                'birth_date' => '1980-01-01'
            ]),
            'ai_abstract' => 'Documento di identità elettronico italiano valido e leggibile.',
            'ai_confidence_score' => 98,
            'is_template' => 0,
            'is_signed' => 0,
            'is_unique' => 1,
            'is_endMonth' => 0,
            'emitted_by' => 'Comune di Roma',
            'emitted_at' => '2020-05-15',
            'expires_at' => '2030-05-15',
            'description' => "Scansione fronte/retro della carta d'identità",
            'internal_notes' => 'Verificata tramite OCR automatico. Nessuna anomalia rilevata.',
            'user_id' => 1,
            'uploaded_by' => 1,
            'verified_by' => 2,  // ID di un admin
            'verified_at' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
