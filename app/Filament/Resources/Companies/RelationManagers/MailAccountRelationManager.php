<?php

namespace App\Filament\Resources\Companies\RelationManagers;

use App\Filament\Resources\MailAccounts\MailAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

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
