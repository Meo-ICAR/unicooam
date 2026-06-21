<?php

namespace App\Filament\Resources\Remediations;

use App\Filament\Resources\Remediations\Pages\CreateRemediation;
use App\Filament\Resources\Remediations\Pages\EditRemediation;
use App\Filament\Resources\Remediations\Pages\ListRemediations;
use App\Filament\Resources\Remediations\Schemas\RemediationForm;
use App\Filament\Resources\Remediations\Tables\RemediationsTable;
use App\Models\COMPILANCE\Remediation;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use BackedEnum;

class RemediationResource extends Resource
{
    protected static ?string $model = Remediation::class;

    protected static bool $isScopedToTenant = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\UnitEnum|null $navigationGroup = 'Privacy & GDPR';

    protected static ?int $navigationSort = 95;

    public static function form(Schema $schema): Schema
    {
        return RemediationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RemediationsTable::configure($table);
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
            'index' => ListRemediations::route('/'),
            'create' => CreateRemediation::route('/create'),
            'edit' => EditRemediation::route('/{record}/edit'),
        ];
    }
}
