<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('branches')->insert([
            'company_id' => '45d36df8-369f-40ce-b4fd-b5907c342fe9',  // ID della Races Finance
            'name' => 'Sede Centrale Roma',
            // Esempio di legame polimorfico (se non è legata a un sotto-modello specifico puoi lasciarli null)
            'branchable_type' => 'App\Models\Company',
            'branchable_id' => '45d36df8-369f-40ce-b4fd-b5907c342fe9',
            'is_main_office' => 1,  // Sede principale
            'manager_first_name' => 'Mario',
            'manager_last_name' => 'Rossi',
            'manager_tax_code' => 'RSSMRA80A01H501W',
            'founded_at' => '2012-11-26 09:00:00',
            'dismissed_at' => null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
