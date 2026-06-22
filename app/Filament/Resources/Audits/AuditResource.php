<?php

namespace App\Filament\Resources\Audits;

use App\Filament\Resources\Audits\Pages\CreateAudit;
use App\Filament\Resources\Audits\Pages\EditAudit;
use App\Filament\Resources\Audits\Pages\ListAudits;
use App\Filament\Resources\Audits\RelationManagers\FindingsRelationManager;
use App\Filament\Resources\Audits\Schemas\AuditForm;
use App\Filament\Resources\Audits\Tables\AuditsTable;
use App\Filament\Resources\RelationManagers\DocumentsRelationManager;
use App\Models\Audit;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use BackedEnum;

class AuditResource extends Resource
{
    protected static ?string $model = Audit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = 'Audit';

    protected static ?string $modelLabel = 'Audit';

    protected static ?string $pluralModelLabel = 'Audit';

    //     protected static string|\UnitEnum|null $navigationGroup = 'Impostazioni';

    public static function form(Schema $schema): Schema
    {
        return AuditForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AuditsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            FindingsRelationManager::class,
            DocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAudits::route('/'),
            'create' => CreateAudit::route('/create'),
            'edit' => EditAudit::route('/{record}/edit'),
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
