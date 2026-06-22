<?php

namespace App\Filament\Widgets;

use App\Models\AuditFinding;
use App\Models\CompanyInspection;
use App\Models\ComplaintRegistry;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ComplianceStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Reclami Aperti', ComplaintRegistry::whereNotIn('status', ['Accepted', 'Rejected'])->count())
                ->description('Da gestire o in scadenza')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('warning'),
            Stat::make('Rilievi (Findings) Aperti', AuditFinding::whereNotIn('status', ['Resolved', 'Closed', 'AcceptedRisk'])->count())
                ->description('Richiedono azione correttiva')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('danger'),
            Stat::make('Ispezioni nel Semestre', CompanyInspection::count())
                ->description('Programmate o completate')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('success'),
        ];
    }
}
