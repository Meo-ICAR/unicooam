<?php

namespace App\Filament\Resources\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WebsitesRelationManager extends RelationManager
{
    protected static string $relationship = 'websites';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name'),
                TextInput::make('name')
                    ->required(),
                TextInput::make('type'),
                TextInput::make('clienti_id')
                    ->numeric(),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('domain')
                    ->required(),
                Toggle::make('is_typical')
                    ->required(),
                DatePicker::make('privacy_date'),
                DatePicker::make('transparency_date'),
                DatePicker::make('privacy_prior_date'),
                DatePicker::make('transparency_prior_date'),
                TextInput::make('url_privacy'),
                TextInput::make('url_cookies'),
                Toggle::make('is_footercompilant')
                    ->required(),
                TextInput::make('url_transparency'),
                Toggle::make('is_iso27001_certified')
                    ->required(),
                TextInput::make('websiteable_type'),
                TextInput::make('websiteable_id'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('url')
            ->columns([
                TextColumn::make('company.name')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('type')
                    ->searchable(),
                TextColumn::make('clienti_id')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('domain')
                    ->searchable(),
                IconColumn::make('is_typical')
                    ->boolean(),
                TextColumn::make('privacy_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('transparency_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('privacy_prior_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('transparency_prior_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('url_privacy')
                    ->searchable(),
                TextColumn::make('url_cookies')
                    ->searchable(),
                IconColumn::make('is_footercompilant')
                    ->boolean(),
                TextColumn::make('url_transparency')
                    ->searchable(),
                IconColumn::make('is_iso27001_certified')
                    ->boolean(),
                TextColumn::make('websiteable_type')
                    ->searchable(),
                TextColumn::make('websiteable_id')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->headerActions([
                CreateAction::make(),
                AssociateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DissociateAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ]));
    }
}
