<?php

namespace App\Filament\Resources\Employees\Schemas;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ImportAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Informazioni Anagrafiche
                Section::make('Informazioni Anagrafiche')
                    ->description('Dati principali del dipendente')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nominativo')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('pec')
                            ->label('PEC')
                            ->email()
                            ->nullable()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('Telefono')
                            ->tel()
                            ->nullable()
                            ->maxLength(20),
                        Select::make('employment_type_id')
                            ->label('Mansione')
                            ->relationship('employmentType', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),
                // Informazioni Lavorative
                Section::make('Informazioni Lavorative')
                    ->description('Dati di impiego e organizzazione')
                    ->schema([
                        Select::make('company_branch_id')
                            ->label('Sede')
                            ->relationship('companyBranch', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('coordinated_by_id')
                            ->label('Coordinato da')
                            ->relationship('coordinatedBy', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Seleziona un coordinatore della stessa sede'),
                        TextInput::make('department')
                            ->label('Dipartimento')
                            ->nullable()
                            ->maxLength(100),
                        DatePicker::make('hire_date')
                            ->label('Data Assunzione')
                            ->required(),
                        DatePicker::make('termination_date')
                            ->label('Data Cessazione')
                            ->nullable()
                            ->after('hire_date'),
                    ]),
                // Dati OAM e RUI
                Section::make('Dati OAM e RUI')
                    ->description('Informazioni per iscrizioni OAM e RUI')
                    ->schema([
                        Toggle::make('numero_iscrizione_rui')
                            ->label('Iscritto OAM')
                            ->reactive(),
                        TextInput::make('oam')
                            ->label('Numero Iscrizione OAM')
                            ->maxLength(50)
                            ->nullable(),
                        DatePicker::make('oam_at')
                            ->label('Data Iscrizione OAM')
                            ->nullable()
                            ->visible(fn(callable $get) => $get('numero_iscrizione_rui')),
                        TextInput::make('oam_name')
                            ->label('Nome OAM')
                            ->maxLength(255)
                            ->nullable()
                            ->visible(fn(callable $get) => $get('numero_iscrizione_rui')),
                        DatePicker::make('oam_dismissed_at')
                            ->label('Data Cancellazione OAM')
                            ->nullable()
                            ->visible(fn(callable $get) => $get('numero_iscrizione_rui')),
                    ]),
                // Documenti
                Section::make('Documenti')
                    ->description('Documenti associati al dipendente')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('documents')
                            ->label('Documenti')
                            ->multiple()
                            ->reorderable()
                            ->collection('employee_documents')
                            ->directory('employee-documents')
                            ->visibility('private'),
                    ]),
            ]);
    }
}
