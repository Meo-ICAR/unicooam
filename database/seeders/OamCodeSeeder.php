<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OamCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $oamCodes = [
            ['id' => 1, 'code' => 'A.1', 'name' => 'Mutui', 'description' => 'A.1 Mutui', 'tipo_prodotto' => 'Mutuo', 'created_at' => '2026-03-16 04:20:44', 'updated_at' => '2026-03-16 04:20:44'],
            ['id' => 2, 'code' => 'A.2', 'name' => 'Cessioni del V dello stipendio/pensione e delegazioni di pagamento', 'description' => 'A.2 Cessioni del V dello stipendio/pensione e delegazioni di pagamento', 'tipo_prodotto' => 'Cessione', 'created_at' => '2026-03-16 04:20:44', 'updated_at' => '2026-03-16 04:20:44'],
            ['id' => 3, 'code' => 'A.3', 'name' => 'Factoring crediti', 'description' => 'A.3 Factoring crediti', 'tipo_prodotto' => null, 'created_at' => '2026-03-16 04:20:44', 'updated_at' => '2026-03-16 04:20:44'],
            ['id' => 4, 'code' => 'A.4', 'name' => 'Acquisto di crediti', 'description' => 'A.4 Acquisto di crediti', 'tipo_prodotto' => null, 'created_at' => '2026-03-16 04:20:44', 'updated_at' => '2026-03-16 04:20:44'],
            ['id' => 5, 'code' => 'A.5', 'name' => 'Leasing autoveicoli e aeronavali', 'description' => 'A.5 Leasing autoveicoli e aeronavali', 'tipo_prodotto' => null, 'created_at' => '2026-03-16 04:20:44', 'updated_at' => '2026-03-16 04:20:44'],
            ['id' => 6, 'code' => 'A.6', 'name' => 'Leasing immobiliare', 'description' => 'A.6 Leasing immobiliare', 'tipo_prodotto' => null, 'created_at' => '2026-03-16 04:20:44', 'updated_at' => '2026-03-16 04:20:44'],
            ['id' => 7, 'code' => 'A.7', 'name' => 'Leasing strumentale', 'description' => 'A.7 Leasing strumentale', 'tipo_prodotto' => null, 'created_at' => '2026-03-16 04:20:44', 'updated_at' => '2026-03-16 04:20:44'],
            ['id' => 8, 'code' => 'A.8', 'name' => 'Leasing su fonti rinnovabili ed altre tipologie di investimento', 'description' => 'A.8 Leasing su fonti rinnovabili ed altre tipologie di investimento', 'tipo_prodotto' => null, 'created_at' => '2026-03-16 04:20:44', 'updated_at' => '2026-03-16 04:20:44'],
            ['id' => 9, 'code' => 'A.9', 'name' => 'Aperture di credito in conto corrente', 'description' => 'A.9 Aperture di credito in conto corrente', 'tipo_prodotto' => null, 'created_at' => '2026-03-16 04:20:44', 'updated_at' => '2026-03-16 04:20:44'],
            ['id' => 10, 'code' => 'A.10', 'name' => 'Credito personale', 'description' => 'A.10 Credito personale', 'tipo_prodotto' => 'Prestito', 'created_at' => '2026-03-16 04:20:44', 'updated_at' => '2026-03-16 04:20:44'],
            ['id' => 11, 'code' => 'A.11', 'name' => 'Credito finalizzato', 'description' => 'A.11 Credito finalizzato', 'tipo_prodotto' => 'Chirografario', 'created_at' => '2026-03-16 04:20:44', 'updated_at' => '2026-03-16 04:20:44'],
            ['id' => 12, 'code' => 'A.12', 'name' => 'Prestito su pegno', 'description' => 'A.12 Prestito su pegno', 'tipo_prodotto' => null, 'created_at' => '2026-03-16 04:20:44', 'updated_at' => '2026-03-16 04:20:44'],
            ['id' => 13, 'code' => 'A.13', 'name' => 'Rilascio di fidejussioni e garanzie', 'description' => 'A.13 Rilascio di fidejussioni e garanzie', 'tipo_prodotto' => null, 'created_at' => '2026-03-16 04:20:44', 'updated_at' => '2026-03-16 04:20:44'],
            ['id' => 14, 'code' => 'A.13-bis', 'name' => 'Garanzia collettiva dei fidi', 'description' => 'A.13-bis Garanzia collettiva dei fidi', 'tipo_prodotto' => null, 'created_at' => '2026-03-16 04:20:44', 'updated_at' => '2026-03-16 04:20:44'],
            ['id' => 15, 'code' => 'A.14', 'name' => 'Anticipi e sconti commerciali', 'description' => 'A.14 Anticipi e sconti commerciali', 'tipo_prodotto' => null, 'created_at' => '2026-03-16 04:20:44', 'updated_at' => '2026-03-16 04:20:44'],
            ['id' => 16, 'code' => 'A.15', 'name' => 'Credito revolving', 'description' => 'A.15 Credito revolving', 'tipo_prodotto' => null, 'created_at' => '2026-03-16 04:20:44', 'updated_at' => '2026-03-16 04:20:44'],
            ['id' => 17, 'code' => 'A.16', 'name' => 'Ristrutturazione dei crediti (art. 128-quater decies, del TUB)', 'description' => 'A.16 Ristrutturazione dei crediti (art. 128-quater decies, del TUB)', 'tipo_prodotto' => null, 'created_at' => '2026-03-16 04:20:44', 'updated_at' => '2026-03-16 04:20:44'],
            ['id' => 18, 'code' => 'Consulenza', 'name' => ' ', 'description' => 'Consulenza  ', 'tipo_prodotto' => null, 'created_at' => '2026-03-16 04:20:44', 'updated_at' => '2026-03-16 04:20:44'],
            ['id' => 19, 'code' => 'Segnalazione mutuo', 'name' => ' ', 'description' => 'Segnalazione mutuo  ', 'tipo_prodotto' => null, 'created_at' => '2026-03-16 04:20:44', 'updated_at' => '2026-03-16 04:20:44'],
            ['id' => 21, 'code' => 'A.2', 'name' => 'Cessioni del V dello stipendio/pensione e delegazioni di pagamento', 'description' => 'A.2 Cessioni del V dello stipendio/pensione e delegazioni di pagamento', 'tipo_prodotto' => 'Delega', 'created_at' => '2026-03-16 04:20:44', 'updated_at' => '2026-03-16 04:20:44'],
            ['id' => 22, 'code' => 'A.4bis', 'name' => 'TFS', 'description' => 'A.4bis TFS', 'tipo_prodotto' => 'TFS', 'created_at' => '2026-03-16 04:20:44', 'updated_at' => '2026-03-16 04:20:44'],
            ['id' => 23, 'code' => 'A.10', 'name' => 'Credito personale', 'description' => 'A.10 Credito personale', 'tipo_prodotto' => 'Microcredito', 'created_at' => '2026-03-16 04:20:44', 'updated_at' => '2026-03-16 04:20:44'],
            ['id' => 24, 'code' => 'A.11', 'name' => 'Credito finalizzato', 'description' => 'A.11 Credito finalizzato', 'tipo_prodotto' => 'Aziendale', 'created_at' => '2026-03-16 04:20:44', 'updated_at' => '2026-03-16 04:20:44'],
        ];

        // Se la tabella potrebbe contenere già questi ID, 'upsert' previene errori di chiave duplicata.
        // In alternativa, se si tratta di un'installazione pulita, 'insert' va benissimo.
        DB::table('oam_codes')->upsert($oamCodes, ['id'], ['code', 'name', 'description', 'tipo_prodotto', 'updated_at']);
    }
}
