<?php

namespace App\Filament\Resources\SuspiciousActivityReports;

use App\Filament\Resources\SuspiciousActivityReports\Pages\CreateSuspiciousActivityReport;
use App\Filament\Resources\SuspiciousActivityReports\Pages\EditSuspiciousActivityReport;
use App\Filament\Resources\SuspiciousActivityReports\Pages\ListSuspiciousActivityReports;
use App\Filament\Resources\SuspiciousActivityReports\Schemas\SuspiciousActivityReportForm;
use App\Filament\Resources\SuspiciousActivityReports\Tables\SuspiciousActivityReportsTable;
use App\Models\SuspiciousActivityReport;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use BackedEnum;

class SuspiciousActivityReportResource extends Resource
{
    protected static ?string $model = SuspiciousActivityReport::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';  // Heroicon::OutlinedFlag;

    protected static ?string $recordTitleAttribute = 'description';

    protected static ?string $navigationLabel = 'Segnalazioni SOS';

    protected static ?string $modelLabel = 'Segnalazione SOS';

    protected static ?string $pluralModelLabel = 'Segnalazioni SOS';

    // protected static string|\UnitEnum|null $navigationGroup = 'Antiriciclaggio';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return SuspiciousActivityReportForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SuspiciousActivityReportsTable::configure($table);
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
            'index' => ListSuspiciousActivityReports::route('/'),
            'create' => CreateSuspiciousActivityReport::route('/create'),
            'edit' => EditSuspiciousActivityReport::route('/{record}/edit'),
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
