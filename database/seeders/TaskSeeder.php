<?php

namespace Database\Seeders;

use App\Models\Task;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $tasks = [
            [
                'name' => 'OnBoarding',
                'description' => 'Attività e documenti richiesti per il caricamento di una nuova anagrafica.',
            ],
            [
                'name' => 'Renew',
                'description' => 'Attività e controlli per il rinnovo periodico delle convenzioni o contratti.',
            ],
            [
                'name' => 'Audit',
                'description' => 'Attività di controllo conformità e verifica della documentazione interna.',
            ],
            [
                'name' => 'OffBoarding',
                'description' => 'Attività e documenti richiesti per la chiusura di una anagrafica.',
            ],
        ];

        foreach ($tasks as $task) {
            Task::firstOrCreate(['name' => $task['name']], $task);
        }
    }
}
