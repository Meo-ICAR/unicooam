<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $organizations = [
            [
                'acronym' => 'OAM',
                'name' => 'Organismo Agenti e Mediatori',
                'description' => 'Gestione degli Elenchi degli Agenti in attività finanziaria e dei Mediatori creditizi.',
                'reference_law' => 'D.Lgs. 141/2010',
                'website' => 'https://www.organismo-am.it',
            ],
            [
                'acronym' => "Banca d'Italia",
                'name' => "Banca d'Italia",
                'description' => 'Supervisione sul sistema bancario e finanziario, trasparenza e correttezza dei comportamenti.',
                'reference_law' => 'TUB (Testo Unico Bancario)',
                'website' => 'https://www.bancaditalia.it',
            ],
            [
                'acronym' => 'UIF',
                'name' => "Unità di Informazione Finanziaria per l'Italia",
                'description' => 'Contrasto del riciclaggio e del finanziamento del terrorismo (AML/CFT).',
                'reference_law' => 'D.Lgs. 231/2007',
                'website' => 'https://uif.bancaditalia.it',
            ],
            [
                'acronym' => 'IVASS',
                'name' => 'Istituto per la Vigilanza sulle Assicurazioni',
                'description' => 'Vigilanza sul mercato assicurativo (rilevante se il mediatore colloca polizze accessorie).',
                'reference_law' => 'D.Lgs. 209/2005 (Codice delle Assicurazioni Private)',
                'website' => 'https://www.ivass.it',
            ],
        ];

        foreach ($organizations as $organization) {
            Organization::updateOrCreate(['acronym' => $organization['acronym']], $organization);
        }
    }
}
