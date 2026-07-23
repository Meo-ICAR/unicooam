<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use BackedEnum;
use UnitEnum;

class Manuale extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';
    public string $view = 'filament.pages.manuale';
    protected static ?string $navigationLabel = 'Manuale Utente';
    protected static ?string $title = 'Manuale Utente';
    protected static ?string $slug = 'manuale';
    protected static ?int $navigationSort = 100;

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
}
