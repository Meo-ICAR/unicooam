<?php

namespace App\Filament\Resources\Fornitores\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FornitoreForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dati Anagrafici e Contatti')
                    ->description("Informazioni personali e di contatto dell'agente o fornitore.")
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('Ragione Sociale / Nome Azienda')
                                ->maxLength(255),
                            TextInput::make('nome')
                                ->label('Nome del Referente')
                                ->maxLength(255),
                            TextInput::make('piva')
                                ->label('Partita IVA')
                                ->maxLength(20),
                            TextInput::make('cf')
                                ->label('Codice Fiscale')
                                ->maxLength(16),
                            DatePicker::make('natoil')
                                ->label('Data di Nascita')
                                ->displayFormat('d/m/Y'),
                            TextInput::make('tel')
                                ->label('Telefono')
                                ->tel()
                                ->maxLength(255),
                            TextInput::make('email')
                                ->label('Indirizzo email')
                                ->email()
                                ->maxLength(255),
                            TextInput::make('pec')
                                ->label('PEC (Posta Elettronica Certificata)')
                                ->email()
                                ->maxLength(255),
                            TextInput::make('dpo_email')
                                ->label('Indirizzo email privata')
                                ->email()
                                ->maxLength(255),
                        ]),
                        Grid::make(3)->schema([
                            TextInput::make('indirizzo')
                                ->label('Indirizzo')
                                ->maxLength(255)
                                ->columnSpan(3),
                            TextInput::make('comune')
                                ->label('Comune')
                                ->maxLength(255),
                            TextInput::make('cap')
                                ->label('CAP')
                                ->maxLength(255),
                            TextInput::make('prov')
                                ->label('Provincia')
                                ->maxLength(255),
                            TextInput::make('regione')
                                ->label('Regione')
                                ->maxLength(255),
                            TextInput::make('citta')
                                ->label('Città')
                                ->maxLength(255),
                        ]),
                    ])
                    ->columnSpanFull(),
                // 2. ISCRIZIONI E ALBI (OAM / IVASS)
                Section::make('Iscrizioni e Albi')
                    ->schema([
                        Grid::make(2)->schema([
                            // OAM
                            TextInput::make('oam')
                                ->label('Codice OAM')
                                ->maxLength(30),
                            TextInput::make('numero_iscrizione_rui')
                                ->label('Numero iscrizione OAM (RUI)')
                                ->maxLength(50),
                            TextInput::make('oam_name')
                                ->label('Denominazione OAM')
                                ->maxLength(255)
                                ->columnSpan(2),
                            DatePicker::make('oam_at')
                                ->label('Data iscrizione OAM')
                                ->displayFormat('d/m/Y'),
                            DatePicker::make('oam_dismissed_at')
                                ->label('Data revoca OAM')
                                ->displayFormat('d/m/Y'),
                            // IVASS
                            TextInput::make('ivass')
                                ->label('Codice IVASS')
                                ->maxLength(30),
                            Select::make('ivass_section')
                                ->label('Sezione IVASS')
                                ->options([
                                    'A' => 'A',
                                    'B' => 'B',
                                    'C' => 'C',
                                    'D' => 'D',
                                    'E' => 'E',
                                ]),
                            TextInput::make('ivass_name')
                                ->label('Denominazione IVASS')
                                ->maxLength(255)
                                ->columnSpan(2),
                            DatePicker::make('ivass_at')
                                ->label('Data iscrizione IVASS')
                                ->displayFormat('d/m/Y'),
                        ]),
                    ])
                    ->collapsed(),
                // 3. DATI CONTRATTUALI E INQUADRAMENTO
                Section::make('Dati Contrattuali e Inquadramento')
                    ->schema([
                        Grid::make(3)->schema([
                            Toggle::make('is_active')
                                ->label('Agente Attivo')
                                ->default(true)
                                ->inline(false),
                            Toggle::make('is_art108')
                                ->label('Esente art. 108')
                                ->default(false)
                                ->inline(false),
                            Toggle::make('iscollaboratore')
                                ->label('È un collaboratore')
                                ->inline(false),
                            Toggle::make('isdipendente')
                                ->label('È un dipendente')
                                ->default(false)
                                ->inline(false),
                            Toggle::make('issubfornitore')
                                ->label('Sub-fornitore')
                                ->inline(false),
                        ]),
                        Grid::make(2)->schema([
                            Select::make('enasarco')
                                ->label('Mandato ENASARCO')
                                ->options([
                                    'no' => 'Nessuno',
                                    'monomandatario' => 'Monomandatario',
                                    'plurimandatario' => 'Plurimandatario',
                                    'societa' => 'Società',
                                ])
                                ->default('plurimandatario'),
                            TextInput::make('type')
                                ->label('Tipologia')
                                ->helperText('Agente / Mediatore / Consulente / Call Center')
                                ->maxLength(30),
                            Select::make('supervisor_type')
                                ->label('Tipo Supervisore')
                                ->options([
                                    'no' => 'Nessuno',
                                    'si' => 'Sì',
                                    'filiale' => 'Filiale',
                                ])
                                ->default('no')
                                ->required(),
                            TextInput::make('coordinatore')
                                ->label('Nome Coordinatore')
                                ->maxLength(255),
                            DatePicker::make('stipulated_at')
                                ->label('Data Stipula Contratto')
                                ->displayFormat('d/m/Y'),
                            DatePicker::make('dismissed_at')
                                ->label('Data Cessazione Rapporto')
                                ->displayFormat('d/m/Y'),
                            DatePicker::make('available_at')
                                ->label('Data Disponibilità')
                                ->displayFormat('d/m/Y'),
                            TextInput::make('campagna')
                                ->label('Codice Campagna')
                                ->maxLength(255),
                        ]),
                    ])
                    ->collapsed(),
                // 4. DATI ECONOMICI E CONTABILI
                Section::make('Dati Economici e Amministrativi')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('codice')
                                ->label('Codice Gestionale')
                                ->maxLength(255),
                            TextInput::make('coge')
                                ->label('Codice COGE')
                                ->maxLength(255),
                            TextInput::make('nomecoge')
                                ->label('Nome per Contabilità')
                                ->maxLength(255),
                            TextInput::make('nomefattura')
                                ->label('Nome in Fattura')
                                ->maxLength(255),
                        ]),
                        Grid::make(3)->schema([
                            TextInput::make('budget')
                                ->label('Budget previsto')
                                ->numeric()
                                ->prefix('€'),
                            TextInput::make('welcome_bonus')
                                ->label('Premio di Benvenuto')
                                ->numeric()
                                ->prefix('€'),
                            TextInput::make('anticipo')
                                ->label('Anticipo Mensile')
                                ->numeric()
                                ->prefix('€'),
                            TextInput::make('anticipo_description')
                                ->label('Descrizione Anticipo')
                                ->maxLength(255)
                                ->default('Anticipo attuale'),
                            TextInput::make('anticipo_residuo')
                                ->label('Anticipo Residuo (Debito)')
                                ->numeric()
                                ->prefix('€'),
                            TextInput::make('contributo')
                                ->label('Contributo Spese')
                                ->numeric()
                                ->prefix('€'),
                            TextInput::make('contributo_description')
                                ->label('Descrizione Contributo')
                                ->maxLength(255)
                                ->default('Contributo spese'),
                            TextInput::make('contributoperiodicita')
                                ->label('Periodicità Contributo')
                                ->helperText('Es: 1 = Mensile, 3 = Trimestrale')
                                ->numeric(),
                            DatePicker::make('contributodalmese')
                                ->label('Contributo Dal Mese')
                                ->displayFormat('m/Y'),
                        ]),
                    ])
                    ->collapsed(),
                // 5. RELAZIONI E ID DI SISTEMA
                Section::make('Sistema e Relazioni')
                    ->schema([
                        Grid::make(3)->schema([
                            // Se hai relazioni Eloquent definite, puoi sostituire questi TextInput con dei Select::make()->relationship(...)
                            TextInput::make('company_id')
                                ->label('ID Company')
                                ->default('5c044917-15b3-4471-90c9-38061fcca754')
                                ->maxLength(36),
                            TextInput::make('company_branch_id')
                                ->label('ID Filiale')
                                ->numeric(),
                            TextInput::make('user_id')
                                ->label('ID Utente Collegato')
                                ->numeric(),
                            TextInput::make('coordinated_id')
                                ->label('ID Agente Coordinatore')
                                ->numeric(),
                            TextInput::make('coordinated_type')
                                ->label('ID Dipendente Coordinatore')
                                ->numeric(),
                            TextInput::make('operatore')
                                ->label('Operatore')
                                ->maxLength(255),
                            TextInput::make('description')
                                ->label('Note / Descrizione Aggiuntiva')
                                ->maxLength(255)
                                ->columnSpanFull(),
                        ]),
                    ])
                    ->collapsed(),
            ]);
    }
}
