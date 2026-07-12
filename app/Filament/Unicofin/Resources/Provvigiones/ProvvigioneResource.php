<?php

namespace App\Filament\Unicofin\Resources\Provvigiones;

use App\Filament\Unicofin\Resources\Provvigiones\Pages\CreateProvvigione;
use App\Filament\Unicofin\Resources\Provvigiones\Pages\EditProvvigione;
use App\Filament\Unicofin\Resources\Provvigiones\Pages\ListProvvigiones;
use App\Filament\Unicofin\Resources\Provvigiones\Schemas\ProvvigioneForm;
use App\Filament\Unicofin\Resources\Provvigiones\Tables\ProvvigionesTable;
use App\Models\Provvigione;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProvvigioneResource extends Resource
{
    protected static ?string $model = Provvigione::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ProvvigioneForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProvvigionesTable::configure($table);
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
            'index' => ListProvvigiones::route('/'),
            'create' => CreateProvvigione::route('/create'),
            'edit' => EditProvvigione::route('/{record}/edit'),
        ];
    }
}
