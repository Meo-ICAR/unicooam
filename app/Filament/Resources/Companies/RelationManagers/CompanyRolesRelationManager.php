<?php

namespace App\Filament\Resources\Companies\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CompanyRolesRelationManager extends RelationManager
{
    protected static string $relationship = 'companyRoles';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name'),
                Select::make('funzione')
                    ->options([
            'INTERNAL AUDIT' => 'I n t e r n a l a u d i t',
            'COMPLIANCE' => 'C o m p l i a n c e',
            'AML' => 'A m l',
            'ALTRO' => 'A l t r o',
        ]),
                Toggle::make('is_external'),
                DatePicker::make('dal'),
                DatePicker::make('al'),
                Select::make('execution_method')
                    ->options(['documentale' => 'Documentale', '' => '', 'onsite' => 'Onsite']),
                TextInput::make('expertName'),
                TextInput::make('n')
                    ->numeric(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('funzione')
                    ->badge(),
                IconColumn::make('is_external')
                    ->boolean(),
                TextColumn::make('dal')
                    ->date()
                    ->sortable(),
                TextColumn::make('al')
                    ->date()
                    ->sortable(),
                TextColumn::make('execution_method')
                    ->badge(),
                TextColumn::make('expertName')
                    ->searchable(),
                TextColumn::make('n')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
                AssociateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DissociateAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
