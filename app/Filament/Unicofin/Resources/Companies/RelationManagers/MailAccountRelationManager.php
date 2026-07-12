<?php

namespace App\Filament\Unicofin\Resources\Companies\RelationManagers;

use App\Filament\Unicofin\Resources\MailAccounts\MailAccountResource;
use Filament\Actions\CreateAction;
use Filament\Tables\Table;
use Filament\Unicofin\Resources\RelationManagers\RelationManager;

class MailAccountRelationManager extends RelationManager
{
    protected static string $relationship = 'mailAccount';

    protected static ?string $relatedResource = MailAccountResource::class;

    protected static ?string $title = 'Account email';

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
