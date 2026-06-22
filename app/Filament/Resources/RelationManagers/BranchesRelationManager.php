<?php

namespace App\Filament\Resources\RelationManagers;

use App\Models\Branch;
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
use BackedEnum;

class BranchesRelationManager extends RelationManager
{
    protected static string $relationship = 'branches';

    protected static ?string $title = 'Filiali';

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
                TextInput::make('address')
                    ->label('Via / Piazza'),
                TextInput::make('street_number')
                    ->label('Numero civico')
                    ->maxLength(20),
                TextInput::make('city')
                    ->label('Città'),
                TextInput::make('zip_code')
                    ->label('CAP')
                    ->maxLength(10),
                TextInput::make('province')
                    ->label('Provincia'),
                TextInput::make('region')
                    ->label('Regione'),
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
                    ->label('Sede principale')
                    ->boolean(),
                TextColumn::make('address')
                    ->label('Indirizzo')
                    ->formatStateUsing(fn($record) => trim(($record->address ?? '') . ' ' . ($record->street_number ?? '')))
                    ->searchable(),
                TextColumn::make('city')
                    ->label('Città')
                    ->searchable(),
                TextColumn::make('province')
                    ->label('Provincia'),
                TextColumn::make('manager_last_name')
                    ->label('Responsabile')
                    ->formatStateUsing(fn($record) => trim(($record->manager_last_name ?? '') . ' ' . ($record->manager_first_name ?? '')))
                    ->searchable(),
                TextColumn::make('founded_at')
                    ->label('Data apertura')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('dismissed_at')
                    ->label('Data chiusura')
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
            ->modifyQueryUsing(fn(Builder $query) => $query
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ]));
    }
}
