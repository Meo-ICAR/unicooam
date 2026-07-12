<?php

namespace App\Filament\Unicofin\Resources\Clients;

use App\Filament\Unicofin\Resources\Clients\Pages\CreateClient;
use App\Filament\Unicofin\Resources\Clients\Pages\EditClient;
use App\Filament\Unicofin\Resources\Clients\Pages\ListClients;
use App\Filament\Unicofin\Resources\Clients\Schemas\ClientForm;
use App\Filament\Unicofin\Resources\Clients\Tables\ClientsTable;
use App\Models\Client;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ClientForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClientsTable::configure($table);
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
            'index' => ListClients::route('/'),
            'create' => CreateClient::route('/create'),
            'edit' => EditClient::route('/{record}/edit'),
        ];
    }
}
