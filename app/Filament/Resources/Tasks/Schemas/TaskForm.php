<?php

namespace App\Filament\Resources\Tasks\Schemas;

use App\Filament\Utils\FormHelper;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder; // Importi il tuo helper

class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // ==========================================
                // ANAGRAFICA BASE
                // ==========================================
                TextInput::make('name')
                    ->label('Nome attività')
                    ->required()
                    ->maxLength(255),

                TextInput::make('description')
                    ->label('Descrizione')
                    ->maxLength(255),

                // ==========================================
                // NUOVA SEZIONE: CONTESTO E STRUTTURA WORKFLOW
                // ==========================================
                Section::make('Inquadramento e Struttura')
                    ->description('Definisci a quale entità si riferisce il task, l\'applicazione di pertinenza e l\'eventuale dipendenza gerarchica.')
                    ->schema([
                        // 1. RICHIAMO DEL SELECT DINAMICO DAL MORPHMAP
                        FormHelper::polymorphicSelect(name: 'taskable', label: 'Collegata a'),

                        // Relazione gerarchica per i task figli
                        Select::make('parent_id')
                            ->label('Attività precedente (Padre)')
                            ->relationship(
                                name: 'parent',
                                titleAttribute: 'name',
                                // Evitiamo che un task possa essere padre di se stesso in fase di modifica
                                modifyQueryUsing: fn (Builder $query, $record) => $record
                                    ? $query->where('id', '!=', $record->id)
                                    : $query
                            )
                            ->placeholder('Nessuna (Questo è un task principale / Radice)')
                            ->searchable()
                            ->helperText('Seleziona un task padre se questa attività deve attivarsi solo al completamento di quella precedente.'),

                        // Gestione multi-applicazione e task comuni
                        TextInput::make('app_identifier')
                            ->label('Codice Applicazione')
                            ->placeholder('Lascia in bianco per rendere il task COMUNE a tutte le app')
                            ->maxLength(50)
                            ->helperText('Inserisci l\'identificativo dell\'app (es. app_oam) per limitarne la visibilità, oppure lascialo vuoto.'),
                    ])
                    ->columns(3),

                // ==========================================
                // SEZIONE: FILTRI DI ATTIVAZIONE
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
                            ->live(),
                        TextInput::make('trigger_value')
                            ->label('Valore specifico richiesto')
                            ->placeholder('es. esterno, attivo')
                            ->visible(fn (Get $get) => $get('trigger_state') === 'equals')
                            ->required(fn (Get $get) => $get('trigger_state') === 'equals'),
                    ])
                    ->columns(3),

                // ==========================================
                // SEZIONE: FILTRI DI ESCLUSIONE
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
                            ->live(),
                        TextInput::make('exclude_value')
                            ->label('Valore specifico richiesto')
                            ->placeholder('es. esterno, attivo')
                            ->visible(fn (Get $get) => $get('exclude_state') === 'equals')
                            ->required(fn (Get $get) => $get('exclude_state') === 'equals'),
                    ])
                    ->columns(3),

                // ==========================================
                // SEZIONE DOCUMENTI (Esistente)
                // ==========================================
                Section::make('Associazione documenti')
                    ->description("Seleziona i tipi documento da associare all'attività.")
                    ->schema([
                        Toggle::make('show_only_checked_documents')
                            ->label('Mostra solo i documenti selezionati')
                            ->live()
                            ->columnSpanFull(),

                        CheckboxList::make('documentTypes')
                            ->label('Tipi documento')
                            ->relationship(
                                name: 'documentTypes',
                                titleAttribute: 'name',
                                modifyQueryUsing: function (Builder $query, Get $get) {
                                    if ($get('show_only_checked_documents')) {
                                        $selectedIds = $get('documentTypes') ?? [];

                                        return $query->whereIn($query->qualifyColumn('id'), $selectedIds);
                                    }

                                    return $query;
                                }
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
