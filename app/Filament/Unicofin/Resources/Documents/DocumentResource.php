<?php

namespace App\Filament\Unicofin\Resources\Documents;

use App\Filament\Unicofin\Resources\Documents\Pages\CreateDocument;
use App\Filament\Unicofin\Resources\Documents\Pages\EditDocument;
use App\Filament\Unicofin\Resources\Documents\Pages\ListDocuments;
use App\Filament\Unicofin\Resources\Documents\Schemas\DocumentForm;
use App\Filament\Unicofin\Resources\Documents\Tables\DocumentsTable;
use App\Models\Document;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;


class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Elenco Documenti';

    //      protected static UnitEnum|string|null $navigationGroup = 'Conformità';
    protected static ?int $navigationSort = 40;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'Documento';

    protected static ?string $pluralModelLabel = 'Documenti';

    //  protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return DocumentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocumentsTable::configure($table);
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
            'index' => ListDocuments::route('/'),
            'create' => CreateDocument::route('/create'),
            'edit' => EditDocument::route('/{record}/edit'),
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
