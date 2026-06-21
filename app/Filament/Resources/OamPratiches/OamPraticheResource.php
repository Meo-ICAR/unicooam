<?php

namespace App\Filament\Resources\OamPratiches;

use App\Filament\Resources\OamPratiches\Pages\CreateOamPratiche;
use App\Filament\Resources\OamPratiches\Pages\EditOamPratiche;
use App\Filament\Resources\OamPratiches\Pages\ListOamPratiches;
use App\Filament\Resources\OamPratiches\Schemas\OamPraticheForm;
use App\Filament\Resources\OamPratiches\Tables\OamPratichesTable;
use App\Models\OamPratiche;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use BackedEnum;

class OamPraticheResource extends Resource
{
    protected static ?string $model = OamPratiche::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'prodotto_creditizio';

    protected static ?string $navigationLabel = 'Semestrale Dettaglio';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return OamPraticheForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OamPratichesTable::configure($table);
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
            'index' => ListOamPratiches::route('/'),
            'create' => CreateOamPratiche::route('/create'),
            'edit' => EditOamPratiche::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
