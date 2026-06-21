<?php

namespace App\Filament\Resources\ComplaintRegistries;

use App\Enums\ComplaintStatus;
use App\Filament\Resources\ComplaintRegistries\Pages\CreateComplaintRegistry;
use App\Filament\Resources\ComplaintRegistries\Pages\EditComplaintRegistry;
use App\Filament\Resources\ComplaintRegistries\Pages\ListComplaintRegistries;
use App\Filament\Resources\ComplaintRegistries\Schemas\ComplaintRegistryForm;
use App\Filament\Resources\ComplaintRegistries\Tables\ComplaintRegistriesTable;
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

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $recordTitleAttribute = 'complaint_number';

    protected static ?string $label = 'Registro Reclami';

    protected static ?string $pluralLabel = 'Registro Reclami';

    protected static string|\UnitEnum|null $navigationGroup = 'Compliance';

    protected static ?int $navigationSort = 20;

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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['complaint_number', 'complainant_name', 'description'];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'open')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}
