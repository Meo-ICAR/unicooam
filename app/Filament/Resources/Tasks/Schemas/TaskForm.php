<?php

namespace App\Filament\Resources\Tasks\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome attività')
                    ->required()
                    ->maxLength(255),
                TextInput::make('description')
                    ->label('Descrizione')
                    ->maxLength(255),
                Select::make('taskable')
                    ->label('Collegata a')
                    ->options([
                        'audit' => 'Audit',
                        'company' => 'Azienda',
                        'employee' => 'Dipendente',
                        'clienti' => 'Mandante',
                        'fornitore' => 'Produttore',
                    ])
                    ->searchable()
                    ->required(),
                // ==========================================
                // NUOVA SEZIONE: FILTRI DI ATTIVAZIONE
                // ==========================================
                Section::make('Regole di Attivazione Dinamica')
                    ->description('Configura le condizioni per cui questo task deve attivarsi in base ai dati del record.')
                    ->collapsed()
                    ->schema([
                        TextInput::make('trigger_field')
                            ->label('Nome colonna del Database')
                            ->placeholder('es. data_dimissione, tipo_fornitore')
                            ->helperText("Inserisci il nome esatto del campo sulla tabella dell'entità."),
                        Select::make('trigger_state')
                            ->label('Condizione del campo')
                            ->options([
                                'empty' => 'Deve essere VUOTO (Null / Vuoto)',
                                'filled' => 'Deve essere COMPILATO (Contiene un valore)',
                                'equals' => 'Deve essere UGUALE a un valore specifico',
                            ])
                            ->live(),  // Rende il campo reattivo per aggiornare la form al cambio
                        TextInput::make('trigger_value')
                            ->label('Valore specifico richiesto')
                            ->placeholder('es. esterno, attivo')
                            ->visible(fn($get) => $get('trigger_state') === 'equals')  // Visibile solo se selezioni 'equals'
                            ->required(fn($get) => $get('trigger_state') === 'equals'),  // Obbligatorio solo se visibile
                    ])
                    ->columns(3),
                // ==========================================
                // NUOVA SEZIONE: FILTRI DI Esclusione
                // ==========================================
                Section::make('Regole di Esclusione Dinamica')
                    ->description('Configura le condizioni per cui questo task deve essere escluso in base ai dati del record.')
                    ->collapsed()
                    ->schema([
                        TextInput::make('exclude_field')
                            ->label('Nome colonna del Database')
                            ->placeholder('es. data_dimissione, tipo_fornitore')
                            ->helperText("Inserisci il nome esatto del campo sulla tabella dell'entità."),
                        Select::make('exclude_state')
                            ->label('Condizione del campo')
                            ->options([
                                'empty' => 'Deve essere VUOTO (Null / Vuoto)',
                                'filled' => 'Deve essere COMPILATO (Contiene un valore)',
                                'equals' => 'Deve essere UGUALE a un valore specifico',
                            ])
                            ->live(),  // Rende il campo reattivo per aggiornare la form al cambio
                        TextInput::make('exclude_value')
                            ->label('Valore specifico richiesto')
                            ->placeholder('es. esterno, attivo')
                            ->visible(fn($get) => $get('exclude_state') === 'equals')  // Visibile solo se selezioni 'equals'
                            ->required(fn($get) => $get('exclude_state') === 'equals'),  // Obbligatorio solo se visibile
                    ])
                    ->columns(3),
                // ==========================================
                // SEZIONE DOCUMENTI (Esistente)
                // ==========================================
                Section::make('Associazione documenti')
                    ->description("Seleziona i tipi documento da associare all'attività.")
                    ->schema([
                        CheckboxList::make('documentTypes')
                            ->label('Tipi documento')
                            ->relationship(
                                name: 'documentTypes',
                                titleAttribute: 'name'
                            )
                            ->searchable()
                            ->bulkToggleable()
                            ->columns(3)
                            ->gridDirection('row'),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
