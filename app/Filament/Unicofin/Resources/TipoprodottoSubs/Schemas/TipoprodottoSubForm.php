<?php

namespace App\Filament\Unicofin\Resources\TipoprodottoSubs\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TipoprodottoSubForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dettagli Sottoprodotto')
                    ->description('Gestisci i dettagli e i vincoli del sottoprodotto finanziario.')
                    ->schema([
                        // Mostra il nome del padre in sola lettura
                        // Finto campo testuale per mostrare il padre in sola lettura
                        TextInput::make('padre_visivo')
                            ->label('Prodotto Principale')
                            ->disabled() // Lo rende grigio e non modificabile
                            ->dehydrated(false) // Impedisce a Filament di salvarlo nel DB (evita errori SQL)
                            ->default(function ($livewire) {
                                // In fase di CREAZIONE nel RelationManager
                                if (method_exists($livewire, 'getOwnerRecord') && $livewire->getOwnerRecord()) {
                                    return $livewire->getOwnerRecord()->name;
                                }

                                return null;
                            })
                            ->formatStateUsing(function ($record, $livewire) {
                                // In fase di MODIFICA
                                if ($record && $record->tipoProdotto) {
                                    return $record->tipoProdotto->name;
                                }
                                // Fallback per la creazione
                                if (method_exists($livewire, 'getOwnerRecord') && $livewire->getOwnerRecord()) {
                                    return $livewire->getOwnerRecord()->name;
                                }

                                return null;
                            })
                            ->columnSpanFull(),

                        TextInput::make('name')
                            ->label('Nome Sottoprodotto')
                            ->maxLength(50)
                            ->required(),

                        TextInput::make('code')
                            ->label('Codice')
                            ->maxLength(20)
                            ->extraInputAttributes(['style' => 'text-transform: uppercase;'])
                            ->required(),

                        Textarea::make('vincoli')
                            ->label('Vincoli / Note')
                            // tinytext nel database tiene fino a 255 caratteri
                            ->maxLength(255)
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
