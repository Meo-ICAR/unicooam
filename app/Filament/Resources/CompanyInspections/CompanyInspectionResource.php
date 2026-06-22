<?php

namespace App\Filament\Resources\CompanyInspections;

use App\Filament\Resources\CompanyInspections\Pages\CreateCompanyInspection;
use App\Filament\Resources\CompanyInspections\Pages\EditCompanyInspection;
use App\Filament\Resources\CompanyInspections\Pages\ListCompanyInspections;
use App\Filament\Resources\CompanyInspections\Schemas\CompanyInspectionForm;
use App\Filament\Resources\CompanyInspections\Tables\CompanyInspectionsTable;
use App\Models\CompanyIspection;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CompanyInspectionResource extends Resource
{
    protected static ?string $model = CompanyIspection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    protected static ?string $navigationLabel = 'Ispezioni Aziendali';

    protected static ?string $modelLabel = 'Ispezione';

    protected static ?string $pluralModelLabel = 'Ispezioni Aziendali';

    protected static UnitEnum|string|null $navigationGroup = 'Conformità';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return CompanyInspectionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CompanyInspectionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompanyInspections::route('/'),
            'create' => CreateCompanyInspection::route('/create'),
            'edit' => EditCompanyInspection::route('/{record}/edit'),
        ];
    }
}
