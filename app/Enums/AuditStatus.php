<?php
namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AuditStatus: string implements HasLabel, HasColor
{
    case Planned = 'planned';
    case InProgress = 'in_progress';
    case PendingFollowup = 'pending_followup';
    case Completed = 'completed';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Planned => 'Pianificato',
            self::InProgress => 'In Corso',
            self::PendingFollowup => 'In Attesa di Follow-up',
            self::Completed => 'Completato',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Planned => 'info',
            self::InProgress => 'warning',
            self::PendingFollowup => 'danger',
            self::Completed => 'success',
        };
    }
}
