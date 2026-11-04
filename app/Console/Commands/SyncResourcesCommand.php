<?php

namespace App\Console\Commands;

use App\Filament\Traits\HasPlanAccess;
use App\Models\Resource as ResourceModel;
use Filament\Facades\Filament;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SyncResourcesCommand extends Command
{
    /**
     * Funzioni che richiedono almeno il piano MEDIUM (con BASE restano in menu, barrate e disattivate):
     * scadenziario, audit, SOS, assistente AI, controllo qualita', reclami, anagrafiche delle persone
     * (produttori, organigramma, consulenti, ruoli e uffici) e i Settings tranne codici OAM e utenti.
     *
     * @var list<string>
     */
    private const MEDIUM_PLAN_KEYS = [
        'companies-relations',
        'clientis-relations',
        'export_completo',
        'Anagrafica',
        'Informativo',
        'Sedi',
        'Prudenziale',
        'document-schedules',
        'audits',
        'audit-findings',
        'suspicious-activity-reports',
        'assistente-ai',
        'tasks',
        'document-types',
        'email-templates',
        'mail-accounts',
        'organizations',
        'resources',
        'remediations',
        'quality-reviews',
        'complaint-registries',
        'fornitores',
        'employees',
        'clients',
        'employee-types',
    ];

    protected $signature = 'permissions:sync-resources';

    protected $description = 'Scansiona le risorse Filament e le sincronizza nel DB per l\'applicazione corrente';

    public function handle(): int
    {
        $appName = config('app.name');
        $this->info("Inizio sincronizzazione risorse per l'applicazione: [ {$appName} ]");

        $count = 0;

        foreach (Filament::getPanels() as $panel) {
            foreach ($panel->getResources() as $resourceClass) {
                try {
                    // 1. Ricava la chiave/slug univoca della risorsa
                    $key = method_exists($resourceClass, 'getSlug')
                        ? $resourceClass::getSlug()
                        : Str::kebab(class_basename($resourceClass));

                    // 2. Ricava l'etichetta del modello
                    $name = method_exists($resourceClass, 'getModelLabel')
                        ? Str::title($resourceClass::getModelLabel())
                        : class_basename($resourceClass);

                    // 3. Ricava il gruppo di navigazione usando il metodo pubblico di Filament
                    $group = method_exists($resourceClass, 'getNavigationGroup')
                        ? $resourceClass::getNavigationGroup()
                        : null;

                    // 4. Inserisce o mantiene invariata la risorsa se già presente
                    ResourceModel::firstOrCreate(
                        [
                            'app_name' => $appName,
                            'key' => $key,
                        ],
                        [
                            'name' => $name,
                            'group' => $group,
                            'min_plan' => in_array($key, self::MEDIUM_PLAN_KEYS, true) ? 'MEDIUM' : 'BASE', // Piano minimo di default per le nuove risorse
                        ]
                    );

                    $count++;
                } catch (\Throwable $e) {
                    $this->warn("Impossibile sincronizzare la risorsa {$resourceClass}: ".$e->getMessage());
                }
            }
        }

        // Esportazioni della Semestrale OAM e schede collegate: non sono risorse Filament ma funzioni controllate con checkPiano().
        foreach (['export_completo' => 'Completo', 'Anagrafica' => 'Anagrafica', 'Informativo' => 'Informativo', 'Sedi' => 'Sedi', 'Prudenziale' => 'Prudenziale', 'companies-relations' => 'Schede collegate Azienda', 'clientis-relations' => 'Schede collegate Istituti'] as $key => $name) {
            ResourceModel::firstOrCreate(
                ['app_name' => $appName, 'key' => $key],
                ['name' => $name, 'group' => 'Funzioni', 'min_plan' => 'MEDIUM']
            );
            $count++;
        }

        foreach (Filament::getPanels() as $panel) {
            foreach ($panel->getPages() as $pageClass) {
                if (! in_array(HasPlanAccess::class, class_uses_recursive($pageClass), true)) {
                    continue;
                }

                $key = $pageClass::getSlug();

                ResourceModel::firstOrCreate(
                    ['app_name' => $appName, 'key' => $key],
                    [
                        'name' => $pageClass::getNavigationLabel(),
                        'group' => $pageClass::getNavigationGroup(),
                        'min_plan' => in_array($key, self::MEDIUM_PLAN_KEYS, true) ? 'MEDIUM' : 'BASE',
                    ]
                );

                $count++;
            }
        }

        $this->info("Sincronizzazione completata! Processate {$count} risorse per {$appName}.");

        return Command::SUCCESS;
    }
}
