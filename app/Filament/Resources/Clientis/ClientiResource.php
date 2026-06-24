<?php

namespace App\Filament\Resources\Clientis;

use App\Filament\Resources\Clientis\Pages\CreateClienti;
use App\Filament\Resources\Clientis\Pages\EditClienti;
use App\Filament\Resources\Clientis\Pages\ListClientis;
use App\Filament\Resources\Clientis\Schemas\ClientiForm;
use App\Filament\Resources\Clientis\Tables\ClientisTable;
use App\Filament\Resources\RelationManagers\DocumentsRelationManager;
use App\Models\PROFORMA\Clienti;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use BackedEnum;
use UnitEnum;

class ClientiResource extends Resource
{
    protected static ?string $model = Clienti::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $recordTitleAttribute = 'name';

    protected static UnitEnum|string|null $navigationGroup = 'Anagrafiche';

    protected static ?string $navigationLabel = 'Istituti';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return ClientiForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClientisTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            DocumentsRelationManager::class,
            //   WebsitesRelationManager::class,
            //  BranchesRelationManager::class,
            //   InspectionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClientis::route('/'),
            'create' => CreateClienti::route('/create'),
            'edit' => EditClienti::route('/{record}/edit'),
        ];
    }
}
