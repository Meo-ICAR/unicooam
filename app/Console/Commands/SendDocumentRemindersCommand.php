<?php

namespace App\Console\Commands;

use App\Services\CheckStatus;
use App\Services\DocumentReminderService;

class SendDocumentRemindersCommand extends StatusCheckCommand
{
    protected $signature = 'documents:check-expiring';

    protected $description = 'Stato dello scadenziario documenti: documenti monitorati scaduti o in scadenza (il valore è il loro numero)';

    public function __construct(protected DocumentReminderService $service)
    {
        parent::__construct();
    }

    public function checkStatus(): CheckStatus
    {
        return $this->service->expiryStatus();
    }
}
