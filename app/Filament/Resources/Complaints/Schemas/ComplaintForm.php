<?php

namespace App\Filament\Resources\Complaints\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ComplaintForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('dati_reclamo')
                    ->label('Dati Reclamo')
                    ->description('Informazioni principali del reclamo ricevuto')
                    ->icon('heroicon-o-chat-bubble-oval-left-ellipsis')
                    ->schema([
                        DatePicker::make('received_at')
                            ->label('Data Ricezione')
                            ->required()
                            ->displayFormat('d/m/Y')
                            ->maxDate(now()),
                        TextInput::make('subject')
                            ->label('Oggetto')
                            ->placeholder('Breve descrizione del reclamo')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('description')
                            ->label('Descrizione')
                            ->placeholder('Descrivi dettagliatamente il reclamo...')
                            ->required()
                            ->rows(5)
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('soggetti_coinvolti')
                    ->label('Soggetti Coinvolti')
                    ->description('Cliente e/o dipendente coinvolti nel reclamo')
                    ->icon('heroicon-o-users')
                    ->schema([
                        TextInput::make('client_id')
                            ->label('ID Cliente')
                            ->numeric()
                            ->placeholder('ID cliente BPM'),
                        TextInput::make('employee_id')
                            ->label('ID Dipendente')
                            ->numeric()
                            ->placeholder('ID dipendente BPM'),
                    ])->columns(2),

                Section::make('gestione_stato')
                    ->label('Gestione Stato')
                    ->description('Stato e risoluzione del reclamo')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        Select::make('status')
                            ->label('Stato')
                            ->options([
                                'ricevuto'       => 'Ricevuto',
                                'in_lavorazione' => 'In Lavorazione',
                                'accolto'        => 'Accolto',
                                'respinto'       => 'Respinto',
                            ])
                            ->required()
                            ->default('ricevuto')
                            ->live(),
                        DatePicker::make('resolved_at')
                            ->label('Data Risoluzione')
                            ->displayFormat('d/m/Y')
                            ->visible(fn (callable $get): bool => in_array($get('status'), ['accolto', 'respinto'])),
                    ])->columns(2),
            ]);
    }
}
