<?php

namespace App\Filament\Unicofin\Resources\Clientis;

use App\Filament\Unicofin\Resources\Clientis\Pages\CreateClienti;
use App\Filament\Unicofin\Resources\Clientis\Pages\EditClienti;
use App\Filament\Unicofin\Resources\Clientis\Pages\ListClientis;
use App\Filament\Unicofin\Resources\Clientis\Schemas\ClientiForm;
use App\Filament\Unicofin\Resources\Clientis\Tables\ClientisTable;
use App\Models\Clienti;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ClientiResource extends Resource
{
    protected static ?string $model = Clienti::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ClientiForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClientisTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClientis::route('/'),
            'create' => CreateClienti::route('/create'),
            'edit' => EditClienti::route('/{record}/edit'),
        ];
    }
}
