<?php

namespace App\Filament\Resources\ComplaintRegistries;

use App\Filament\Resources\ComplaintRegistries\Pages\CreateComplaintRegistry;
use App\Filament\Resources\ComplaintRegistries\Pages\EditComplaintRegistry;
use App\Filament\Resources\ComplaintRegistries\Pages\ListComplaintRegistries;
use App\Filament\Resources\ComplaintRegistries\Schemas\ComplaintRegistryForm;
use App\Filament\Resources\ComplaintRegistries\Tables\ComplaintRegistriesTable;
use App\Filament\Resources\RelationManagers\DocumentsRelationManager;
use App\Models\ComplaintRegistry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use BackedEnum;

class ComplaintRegistryResource extends Resource
{
    protected static ?string $model = ComplaintRegistry::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $recordTitleAttribute = 'protocol_number';

    protected static ?string $navigationLabel = 'Reclami';

    protected static ?string $modelLabel = 'Reclamo';

    protected static ?string $pluralModelLabel = 'Reclami';

    //      protected static UnitEnum|string|null $navigationGroup = 'Conformità';

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
            DocumentsRelationManager::class,
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
