<?php

namespace App\Filament\Widgets;

use App\Models\AuditFinding;
// use App\Models\CompanyInspection;
use App\Models\ComplaintRegistry;
use App\Models\Document;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class ComplianceStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Reclami in corso', ComplaintRegistry::whereNotIn('status', ['Accepted', 'Rejected'])->count())
                ->description('Da gestire o in scadenza')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('warning'),
            Stat::make('Audit in corso', AuditFinding::whereNotIn('status', ['Resolved', 'Closed', 'AcceptedRisk'])->count())
                ->description('Richiedono azione correttiva')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('danger'),
            Stat::make('Documenti scaduti', Document::where('expires_at', '<', now())->whereNotNull('expires_at')->where('is_monitored', true)->count())
                ->description('Richiedono rinnovo')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('warning'),

            /*
             * Stat::make('Ispezioni nel Semestre', CompanyInspection::count())
             *     ->description('Programmate o completate')
             *     ->descriptionIcon('heroicon-m-shield-check')
             *     ->color('success'),
             */
        ];
    }
}
