<?php

namespace App\Filament\Resources\OamSemestrales;

use App\Filament\Resources\OamSemestrales\Pages\CreateOamSemestrale;
use App\Filament\Resources\OamSemestrales\Pages\EditOamSemestrale;
use App\Filament\Resources\OamSemestrales\Pages\ListOamSemestrales;
use App\Filament\Resources\OamSemestrales\Schemas\OamSemestraleForm;
use App\Filament\Resources\OamSemestrales\Tables\OamSemestralesTable;
use App\Models\OamSemestrale;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use BackedEnum;

class OamSemestraleResource extends Resource
{
    protected static ?string $model = OamSemestrale::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'OAM Semestrale';

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
