<?php

namespace Database\Seeders;

use App\Models\PROFORMA\Fornitore;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Task;
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
        $company_id = Company::first()->id;
        $tasks = Task::all();

        $fornitori = Fornitore::all();
        $employees = Employee::all();

        // Manteniamo l'Eager Loading per le performance
        $tasks = Task::with('documentTypes')->where('is_active', true)->get();
        $createdCount = 0;

        foreach ($tasks as $task) {
            // Caso AZIENDA: il record id coincide con il $company_id
            if (($task->taskable === 'company') || ($task->name === 'OAM-Semestrale')) {
                $createdCount += $task->createDocumentation($company_id, $company_id, true);
            }

            // Caso FORNITORE: cicliamo sui fornitori e passiamo l'id del singolo fornitore
            if ($task->taskable === 'fornitore') {
                foreach ($fornitori as $fornitore) {
                    $createdCount += $task->createDocumentation($company_id, $fornitore->id, true);
                }
            }

            // Caso DIPENDENTE: cicliamo sui dipendenti e passiamo l'id del singolo dipendente
            if (($task->taskable === 'dipendente')) {
                foreach ($employees as $employee) {
                    $createdCount += $task->createDocumentation($company_id, $employee->id, true);
                }
            }
        }
    }
}
