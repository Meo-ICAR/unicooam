<?php

namespace App\Filament\Resources\AuditFindings;

use App\Filament\Resources\AuditFindings\Pages\CreateAuditFinding;
use App\Filament\Resources\AuditFindings\Pages\EditAuditFinding;
use App\Filament\Resources\AuditFindings\Pages\ListAuditFindings;
use App\Filament\Resources\AuditFindings\Schemas\AuditFindingForm;
use App\Filament\Resources\AuditFindings\Tables\AuditFindingsTable;
use App\Models\AuditFinding;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AuditFindingResource extends Resource
{
    protected static ?string $model = AuditFinding::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return AuditFindingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AuditFindingsTable::configure($table);
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
            'index' => ListAuditFindings::route('/'),
            'create' => CreateAuditFinding::route('/create'),
            'edit' => EditAuditFinding::route('/{record}/edit'),
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
