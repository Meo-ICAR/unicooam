<?php

namespace App\Filament\Resources\Resources\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ResourceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('app_name'),
                TextEntry::make('key'),
                TextEntry::make('name'),
                TextEntry::make('group')
                    ->placeholder('-'),
                TextEntry::make('min_plan')
                    ->badge(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
