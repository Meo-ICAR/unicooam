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

    protected static ?string $modelLabel = 'Sito web';

    protected static ?string $pluralModelLabel = 'Siti web';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('domain')
                    ->label('Dominio')
                    ->openUrlInNewTab()
                    ->url(fn($record) => $record->domain ? (str_starts_with($record->domain, 'http') ? $record->domain : "https://{$record->domain}") : null)
                    ->required(),
                Select::make('type')
                    ->label('Tipologia')
                    ->placeholder('es. social per FB / Istagram, landing mandataria')
                    ->options([
                        'istituzionale' => 'Istituzionale',
                        'social' => 'Social',
                        'landing' => 'Landing',
                        'vetrina' => 'Vetrina',
                        'e-commerce' => 'E-commerce',
                        'altro' => 'Altro',
                    ]),
                TextInput::make('url_transparency')
                    ->label('URL trasparenza')
                    ->openUrlInNewTab()
                    ->visible(fn($get) => $get('type') === 'istituzionale')
                    ->url(fn($record) => $record->url_transparency ? (str_starts_with($record->url_transparency, 'http') ? $record->url_transparency : "https://{$record->url_transparency}") : null),
                DatePicker::make('transparency_date')
                    ->label('Data trasparenza')
                    ->visible(fn($get) => $get('type') === 'istituzionale')
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                TextInput::make('name')
                    ->default(fn($get) => $get('type'))
                    ->label('Nome sito')
                    ->required(),
                Toggle::make('is_active')
                    ->label('Attivo')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('domain')
            ->columns([
                TextColumn::make('domain')
                    ->label('Dominio')
                    ->openUrlInNewTab()
                    ->url(fn($record) => str_starts_with($record->domain, 'http') ? $record->domain : "https://{$record->domain}")
                    ->openUrlInNewTab()
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Tipologia')
                    ->sortable()
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label('Attivo')
                    ->sortable()
                    ->boolean(),
                TextColumn::make('url_transparency')
                    ->label('URL trasparenza')
                    ->openUrlInNewTab()
                    ->url(fn($record) => $record->url_transparency ? (str_starts_with($record->url_transparency, 'http') ? $record->url_transparency : "https://{$record->url_transparency}") : null),
                DatePicker::make('transparency_date')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('transparency_date')
                    ->label('Trasparenza')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable(),

                /*
                 * TextColumn::make('client.name')
                 *  ->label('Mandante')
                 *  ->searchable(),
                 * TextColumn::make('privacy_date')
                 *     ->label('Privacy')
                 *     ->date('d/m/Y')
                 *     ->sortable(),
                 * TextColumn::make('privacy_prior_date')
                 *     ->label('Privacy precedente')
                 *     ->date('d/m/Y')
                 *     ->sortable()
                 *     ->toggleable(isToggledHiddenByDefault: true),
                 * TextColumn::make('transparency_prior_date')
                 *     ->label('Trasparenza precedente')
                 *     ->date('d/m/Y')
                 *     ->sortable()
                 *     ->toggleable(isToggledHiddenByDefault: true),
                 * TextColumn::make('url_privacy')
                 *     ->label('URL privacy')
                 *     ->searchable()
                 *     ->toggleable(isToggledHiddenByDefault: true),
                 * TextColumn::make('url_cookies')
                 *     ->label('URL cookie')
                 *     ->searchable()
                 *     ->toggleable(isToggledHiddenByDefault: true),
                 * IconColumn::make('is_footercompilant')
                 *     ->label('Footer conforme')
                 *     ->boolean()
                 *     ->toggleable(isToggledHiddenByDefault: true),
                 *     IconColumn::make('is_iso27001_certified')
                 *  ->label('ISO 27001')
                 *  ->boolean()
                 *  ->toggleable(isToggledHiddenByDefault: true),
                 */
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Eliminati'),
            ])
            ->headerActions([
                CreateAction::make(),
                //  AssociateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                //   DissociateAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //     DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
