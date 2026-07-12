<?php

namespace App\Filament\Unicofin\Resources\Organizations;

use App\Filament\Unicofin\Resources\Organizations\Pages\CreateOrganization;
use App\Filament\Unicofin\Resources\Organizations\Pages\EditOrganization;
use App\Filament\Unicofin\Resources\Organizations\Pages\ListOrganizations;
use App\Filament\Unicofin\Resources\Organizations\Schemas\OrganizationForm;
use App\Filament\Unicofin\Resources\Organizations\Tables\OrganizationsTable;
use App\Models\Organization;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;
use Filament\Resources\Resource; // <--- AGGIUNTO QUESTO IMPORT MANCANTE

class OrganizationResource extends Resource
{
    protected static ?string $model = Organization::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $recordTitleAttribute = 'name';

    protected static UnitEnum|string|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Organismi';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return OrganizationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrganizationsTable::configure($table);
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
            'index' => ListOrganizations::route('/'),
            'create' => CreateOrganization::route('/create'),
            'edit' => EditOrganization::route('/{record}/edit'),
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
