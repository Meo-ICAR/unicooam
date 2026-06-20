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
use Filament\Forms\Components\DateTimePicker;
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

class BranchesRelationManager extends RelationManager
{
    protected static string $relationship = 'branches';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome della filiale (es. Sede Milano, Ufficio Roma)')
                    ->required(),
                Toggle::make('is_main_office')
                    ->label('Indica se è la Sede Legale/Operativa principale (1 = Sì, 0 = No)')
                    ->required(),
                TextInput::make('manager_first_name')
                    ->label('Nome del responsabile della filiale'),
                TextInput::make('manager_last_name')
                    ->label('Cognome del responsabile della filiale'),
                TextInput::make('manager_tax_code')
                    ->label('Codice Fiscale del responsabile della filiale'),
                DateTimePicker::make('founded_at')
                    ->label('Data e ora di apertura/fondazione della filiale'),
                DateTimePicker::make('dismissed_at')
                    ->label('Data e ora di chiusura/dismissione della filiale'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nome della filiale (es. Sede Milano, Ufficio Roma)')
                    ->searchable(),
                IconColumn::make('is_main_office')
                    ->label('Indica se è la Sede Legale/Operativa principale (1 = Sì, 0 = No)')
                    ->boolean(),
                TextColumn::make('manager_first_name')
                    ->label('Nome del responsabile della filiale')
                    ->searchable(),
                TextColumn::make('manager_last_name')
                    ->label('Cognome del responsabile della filiale')
                    ->searchable(),
                TextColumn::make('manager_tax_code')
                    ->label('Codice Fiscale del responsabile della filiale')
                    ->searchable(),
                TextColumn::make('founded_at')
                    ->label('Data e ora di apertura/fondazione della filiale')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('dismissed_at')
                    ->label('Data e ora di chiusura/dismissione della filiale')
                    ->dateTime()
                    ->sortable(),
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
