<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ComplaintStatus: string implements HasLabel
{
    case OPEN          = 'open';
    case INVESTIGATING = 'investigating';
    case RESOLVED      = 'resolved';
    case REJECTED      = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::OPEN          => 'Aperto',
            self::INVESTIGATING => 'In Investigazione',
            self::RESOLVED      => 'Risolto',
            self::REJECTED      => 'Rifiutato',
        };
    }
}
