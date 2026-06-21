<?php

namespace App\Filament\Resources\OamCodes;

use App\Filament\Resources\OamCodes\Pages\CreateOamCode;
use App\Filament\Resources\OamCodes\Pages\EditOamCode;
use App\Filament\Resources\OamCodes\Pages\ListOamCodes;
use App\Filament\Resources\OamCodes\Schemas\OamCodeForm;
use App\Filament\Resources\OamCodes\Tables\OamCodesTable;
use App\Models\OamCode;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use BackedEnum;
use UnitEnum;

class OamCodeResource extends Resource
{
    protected static ?string $model = OamCode::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'OAM Codici';

    protected static ?int $navigationSort = 2;

    //    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return OamCodeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OamCodesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOamCodes::route('/'),
            'create' => CreateOamCode::route('/create'),
            'edit' => EditOamCode::route('/{record}/edit'),
        ];
    }
}
