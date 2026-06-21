<?php

namespace App\Filament\Resources\ComplaintRegistries;

use App\Filament\Resources\ComplaintRegistries\Pages\CreateComplaintRegistry;
use App\Filament\Resources\ComplaintRegistries\Pages\EditComplaintRegistry;
use App\Filament\Resources\ComplaintRegistries\Pages\ListComplaintRegistries;
use App\Filament\Resources\ComplaintRegistries\Schemas\ComplaintRegistryForm;
use App\Filament\Resources\ComplaintRegistries\Tables\ComplaintRegistriesTable;
use App\Models\ComplaintRegistry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ComplaintRegistryResource extends Resource
{
    protected static ?string $model = ComplaintRegistry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ComplaintRegistryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ComplaintRegistriesTable::configure($table);
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
            'index' => ListComplaintRegistries::route('/'),
            'create' => CreateComplaintRegistry::route('/create'),
            'edit' => EditComplaintRegistry::route('/{record}/edit'),
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
