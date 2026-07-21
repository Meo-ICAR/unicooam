<?php

namespace App\Filament\Resources\OamSemestrales;

use App\Filament\Resources\OamSemestrales\Pages\CreateOamSemestrale;
use App\Filament\Resources\OamSemestrales\Pages\EditOamSemestrale;
use App\Filament\Resources\OamSemestrales\Pages\ListOamSemestrales;
use App\Filament\Resources\OamSemestrales\Schemas\OamSemestraleForm;
use App\Filament\Resources\OamSemestrales\Tables\OamSemestralesTable;
use App\Filament\Traits\HasPlanAccess;
use App\Models\OamSemestrale;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class OamSemestraleResource extends Resource
{
    use HasPlanAccess;

    protected static ?string $model = OamSemestrale::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?string $navigationLabel = 'Semestrale OAM';

    protected static ?string $modelLabel = 'Semestrale OAM';

    protected static ?string $pluralModelLabel = 'Semestrale OAM';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return OamSemestraleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OamSemestralesTable::configure($table);
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
            'index' => ListOamSemestrales::route('/'),
            'create' => CreateOamSemestrale::route('/create'),
            'edit' => EditOamSemestrale::route('/{record}/edit'),
        ];
    }
}
