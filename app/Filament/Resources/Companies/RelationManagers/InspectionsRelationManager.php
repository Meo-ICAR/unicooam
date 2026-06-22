<?php

namespace App\Filament\Resources\Companies\RelationManagers;

use App\Filament\Resources\CompanyInspections\Schemas\CompanyInspectionForm;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InspectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'inspections';

    protected static ?string $title = 'Ispezioni Ricevute';

    public function form(Schema $schema): Schema
    {
        return CompanyInspectionForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('n')
                    ->label('N°')
                    ->sortable()
                    ->width('60px'),
                TextColumn::make('name')
                    ->label('Ispezione')
                    ->searchable(),
                TextColumn::make('execution_method')
                    ->label('Metodo')
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'documentale' => 'Documentale',
                        'onsite' => 'In loco',
                        default => '—',
                    })
                    ->badge(),
                TextColumn::make('dal')
                    ->label('Dal')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('al')
                    ->label('Al')
                    ->date('d/m/Y'),
                TextColumn::make('ispectorName')
                    ->label('Ispettore'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('dal', 'desc');
    }
}
