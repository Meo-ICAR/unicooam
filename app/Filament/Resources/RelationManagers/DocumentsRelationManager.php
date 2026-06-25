<?php

namespace App\Filament\Resources\RelationManagers;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Documenti';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Dettagli Documento')
                ->columns(2)
                ->components([
                    Select::make('document_type_id')
                        ->label('Tipo documento')
                        ->options(DocumentType::orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->required()
                        ->columnSpanFull(),
                    TextInput::make('name')
                        ->label('Nome / Titolo')
                        ->required()
                        ->columnSpanFull(),
                    TextInput::make('docnumber')
                        ->label('Numero documento')
                        ->placeholder('es. CI-2024-001'),
                    Select::make('status')
                        ->label('Stato')
                        ->options(DocumentStatus::class)
                        ->default(DocumentStatus::PENDING)
                        ->required(),
                    DatePicker::make('emitted_at')
                        ->label('Data emissione')
                        ->native(false)
                        ->displayFormat('d/m/Y'),
                    DatePicker::make('expires_at')
                        ->label('Data scadenza')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->afterOrEqual('emitted_at'),
                    TextInput::make('emitted_by')
                        ->label('Emesso da')
                        ->placeholder('es. Comune di Roma'),
                    Toggle::make('is_signed')
                        ->label('Documento firmato'),
                    Textarea::make('description')
                        ->label('Descrizione')
                        ->rows(2)
                        ->columnSpanFull(),
                    Textarea::make('internal_notes')
                        ->label('Note interne')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),
            Section::make('File Allegato')
                ->components([
                    FileUpload::make('attachments')
                        ->label('Carica file (PDF, immagini, Word)')
                        ->multiple()
                        ->disk('public')
                        ->directory('documents')
                        ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                        ->maxSize(20480)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('documentType.name')
                    ->label('Tipo')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Nome documento')
                    ->searchable()
                    ->sortable()
                    ->description(fn($record) => $record->docnumber),
                TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->sortable(),
                TextColumn::make('emitted_at')
                    ->label('Emissione')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label('Scadenza')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn($record) => $record->expires_at?->isPast() ? 'danger' : 'gray')
                    ->weight(fn($record) => $record->expires_at?->isPast() ? 'bold' : 'normal'),
                IconColumn::make('is_signed')
                    ->label('Firmato')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('emitted_by')
                    ->label('Emesso da')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('document_type_id')
                    ->label('Tipo documento')
                    ->relationship('documentType', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->label('Stato')
                    ->options(DocumentStatus::class),
                TrashedFilter::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['company_id'] = $this->getOwnerRecord()->company_id
                            ?? $this->getOwnerRecord()->id;

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('renew')
                    ->label('Rinnove Documento')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    // Mostra il pulsante SOLO se il documento scade ed è monitorato
                    ->visible(fn(Document $record) => $record->documentType->is_monitored)
                    ->action(function (Document $record) {
                        // 1. Archiviamo il documento attuale (opzionale, dipende dal tuo DB)
                        $record->update([
                            'status' => 'expired',  // o 'archived'
                        ]);

                        // 2. Creiamo il nuovo documento per il rinnovo
                        $newDocument = Document::create([
                            'company_id' => $record->company_id,
                            'documentable_type' => $record->documentable_type,
                            'documentable_id' => $record->documentable_id,
                            'document_type_id' => $record->document_type_id,
                            'name' => $record->name,
                            'status' => 'pending',  // Torna in attesa del nuovo file
                            'is_monitored' => true,
                            'expires_at' => null,  // Verrà inserita la nuova scadenza al caricamento
                        ]);

                        Notification::make()
                            ->title('Rinnovo avviato')
                            ->body("È stata creata una nuova richiesta di rinnovo per \"{$record->name}\".")
                            ->success()
                            ->send();
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ])
            ->modifyQueryUsing(fn(Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]))
            ->defaultSort('created_at', 'desc');
    }
}
