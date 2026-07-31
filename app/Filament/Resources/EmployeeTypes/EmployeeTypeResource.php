<?php

namespace App\Filament\Resources\EmployeeTypes;

use App\Filament\Resources\EmployeeTypeResource\RelationManagers\PermissionsRelationManager;
use App\Filament\Resources\EmployeeTypes\Pages\CreateEmployeeType;
use App\Filament\Resources\EmployeeTypes\Pages\EditEmployeeType;
use App\Filament\Resources\EmployeeTypes\Pages\ListEmployeeTypes;
use App\Filament\Resources\EmployeeTypes\Pages\ViewEmployeeType;
use App\Filament\Resources\EmployeeTypes\Schemas\EmployeeTypeForm;
use App\Filament\Resources\EmployeeTypes\Schemas\EmployeeTypeInfolist;
use App\Filament\Resources\EmployeeTypes\Tables\EmployeeTypesTable;
use App\Models\EmployeeType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EmployeeTypeResource extends Resource
{
    protected static ?string $model = EmployeeType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return EmployeeTypeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EmployeeTypeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmployeeTypesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
            PermissionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployeeTypes::route('/'),
            'create' => CreateEmployeeType::route('/create'),
            'view' => ViewEmployeeType::route('/{record}'),
            'edit' => EditEmployeeType::route('/{record}/edit'),
        ];
    }
}
