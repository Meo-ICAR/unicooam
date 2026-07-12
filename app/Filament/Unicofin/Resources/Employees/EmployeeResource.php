<?php

namespace App\Filament\Unicofin\Resources\Employees;

use App\Filament\Unicofin\Resources\Employees\Pages\CreateEmployee;
use App\Filament\Unicofin\Resources\Employees\Pages\EditEmployee;
use App\Filament\Unicofin\Resources\Employees\Pages\ListEmployees;
use App\Filament\Unicofin\Resources\Employees\Schemas\EmployeeForm;
use App\Filament\Unicofin\Resources\Employees\Tables\EmployeesTable;
use App\Filament\Unicofin\Resources\RelationManagers\DocumentsRelationManager;
use App\Models\Employee;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';  // Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static UnitEnum|string|null $navigationGroup = 'Anagrafiche';

    protected static ?string $navigationLabel = 'Dipendenti';

    protected static ?string $modelLabel = 'Dipendente';

    protected static ?string $pluralModelLabel = 'Dipendenti';

    protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        return EmployeeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmployeesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            DocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployees::route('/'),
            'create' => CreateEmployee::route('/create'),
            'edit' => EditEmployee::route('/{record}/edit'),
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
