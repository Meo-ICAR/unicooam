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

    protected static ?string $title = 'Siti web';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->label('Azienda')
                    ->relationship('company', 'name'),
                TextInput::make('name')
                    ->label('Nome sito')
                    ->required(),
                TextInput::make('type')
                    ->label('Tipologia')
                    ->placeholder('es. vetrina, e-commerce, landing'),
                TextInput::make('clienti_id')
                    ->label('ID mandante')
                    ->numeric(),
                Toggle::make('is_active')
                    ->label('Attivo')
                    ->required(),
                TextInput::make('domain')
                    ->label('Dominio')
                    ->required(),
                Toggle::make('is_typical')
                    ->label('Attività tipica')
                    ->required(),
                DatePicker::make('privacy_date')
                    ->label('Data privacy policy')
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                DatePicker::make('transparency_date')
                    ->label('Data trasparenza')
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                DatePicker::make('privacy_prior_date')
                    ->label('Data precedente privacy')
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                DatePicker::make('transparency_prior_date')
                    ->label('Data precedente trasparenza')
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                TextInput::make('url_privacy')
                    ->label('URL privacy policy')
                    ->url(),
                TextInput::make('url_cookies')
                    ->label('URL cookie policy')
                    ->url(),
                Toggle::make('is_footercompilant')
                    ->label('Footer conforme')
                    ->required(),
                TextInput::make('url_transparency')
                    ->label('URL trasparenza')
                    ->url(),
                Toggle::make('is_iso27001_certified')
                    ->label('Certificato ISO 27001')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('domain')
            ->columns([
                TextColumn::make('company.name')
                    ->label('Azienda')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Tipologia')
                    ->searchable(),
                TextColumn::make('clienti_id')
                    ->label('ID mandante')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Attivo')
                    ->boolean(),
                TextColumn::make('domain')
                    ->label('Dominio')
                    ->searchable(),
                IconColumn::make('is_typical')
                    ->label('Attività tipica')
                    ->boolean(),
                TextColumn::make('privacy_date')
                    ->label('Privacy')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('transparency_date')
                    ->label('Trasparenza')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('privacy_prior_date')
                    ->label('Privacy precedente')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('transparency_prior_date')
                    ->label('Trasparenza precedente')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('url_privacy')
                    ->label('URL privacy')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('url_cookies')
                    ->label('URL cookie')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_footercompilant')
                    ->label('Footer conforme')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('url_transparency')
                    ->label('URL trasparenza')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_iso27001_certified')
                    ->label('ISO 27001')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Creato il')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Aggiornato il')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label('Eliminato il')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Eliminati'),
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
