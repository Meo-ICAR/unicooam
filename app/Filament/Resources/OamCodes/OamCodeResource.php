<?php

namespace App\Filament\Resources\OamCodes;

use App\Filament\Resources\OamCodes\Pages\CreateOamCode;
use App\Filament\Resources\OamCodes\Pages\EditOamCode;
use App\Filament\Resources\OamCodes\Pages\ListOamCodes;
use App\Filament\Resources\OamCodes\Schemas\OamCodeForm;
use App\Filament\Resources\OamCodes\Tables\OamCodesTable;
use App\Filament\Traits\HasPlanAccess;
use App\Models\OamCode;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class OamCodeResource extends Resource
{
    // use HasPlanAccess;

    // protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = OamCode::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-hashtag';  // Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static UnitEnum|string|null $navigationGroup = 'Anagrafiche';

    protected static ?string $navigationLabel = 'Codici OAM';

    protected static ?string $modelLabel = 'Codice';

    protected static ?string $pluralModelLabel = 'Codici';

    protected static ?int $navigationSort = 6;

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
