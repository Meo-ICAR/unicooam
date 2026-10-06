<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum SyncStatus: string implements HasColor, HasIcon, HasLabel
{
    case LOCAL = 'local';
    case SYNCING = 'syncing';
    case SYNCED = 'synced';
    case FAILED = 'failed';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::LOCAL => 'Solo Locale',
            self::SYNCING => 'In Sincronizzazione...',
            self::SYNCED => 'Sincronizzato (SharePoint)',
            self::FAILED => 'Errore Sincronizzazione',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::LOCAL => 'gray',
            self::SYNCING => 'info',  // Badge blu
            self::SYNCED => 'success',  // Badge verde
            self::FAILED => 'danger',  // Badge rosso
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::LOCAL => 'heroicon-m-server',
            self::SYNCING => 'heroicon-m-arrow-path',
            self::SYNCED => 'heroicon-m-check-circle',
            self::FAILED => 'heroicon-m-x-circle',
        };
    }
}
