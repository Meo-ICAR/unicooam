<?php

namespace App\Filament\Unicofin\Resources\Fornitores;

use App\Filament\Unicofin\Resources\Fornitores\Pages\CreateFornitore;
use App\Filament\Unicofin\Resources\Fornitores\Pages\EditFornitore;
use App\Filament\Unicofin\Resources\Fornitores\Pages\ListFornitores;
use App\Filament\Unicofin\Resources\Fornitores\Schemas\FornitoreForm;
use App\Filament\Unicofin\Resources\Fornitores\Tables\FornitoresTable;
use App\Models\Fornitore;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FornitoreResource extends Resource
{
    protected static ?string $model = Fornitore::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return FornitoreForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FornitoresTable::configure($table);
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
            'index' => ListFornitores::route('/'),
            'create' => CreateFornitore::route('/create'),
            'edit' => EditFornitore::route('/{record}/edit'),
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
