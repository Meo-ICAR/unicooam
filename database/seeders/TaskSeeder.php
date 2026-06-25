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
                'description' => 'Attività e documenti richiesti per il caricamento di una nuovo produttore.',
                'taskable' => 'fornitore',
                'trigger_field' => 'oam_at',
                'trigger_state' => 'empty',
            ],
            [
                'name' => 'OAM-Agenti',
                'description' => 'Attività e controlli per il rinnovo periodico OAM',
                'taskable' => 'fornitore',
                'trigger_field' => 'oam_at',
                'trigger_state' => 'filled',
            ],
            [
                'name' => 'ISVASS-Agenti',
                'description' => 'Attività e controlli per il rinnovo periodico ISVASS',
                'taskable' => 'fornitore',
                'trigger_field' => 'ivass_section',
                'trigger_state' => 'equals',
                'trigger_value' => 'E',
            ],
            [
                'name' => 'OAM-Semestrale',
                'description' => 'Documenti aziendali semestrale OAM',
                'taskable' => 'company',
            ],
            [
                'name' => 'Renewal',
                'description' => 'Attività e controlli per il rinnovo periodico delle convenzioni o contratti.',
                'taskable' => 'company',
            ],
            [
                'name' => 'Audit',
                'description' => 'Attività di controllo conformità e verifica della documentazione interna.',
            ],
            [
                'name' => 'Ispezione',
                'description' => 'Attività di ispezione e verifica della documentazione.',
            ],
            [
                'name' => 'OffBoarding',
                'description' => 'Attività e documenti richiesti per la chiusura di una anagrafica.',
                'taskable' => 'fornitore',
                'trigger_field' => 'dismissed_at',
                'trigger_state' => 'filled',
            ],
        ];

        foreach ($tasks as $task) {
            Task::firstOrCreate(['name' => $task['name']], $task);
        }
    }
}
