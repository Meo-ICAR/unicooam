<?php

namespace App\Filament\Resources\RelationManagers;

use App\Enums\DocumentStatus;
use App\Filament\Exports\DynamicGroupExport;
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
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;  // CORRETTO
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;
use pxlrbt\FilamentExcel\Actions\ExportAction;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Documenti';

    protected static ?string $modelLabel = 'Documento';

    protected static ?string $pluralModelLabel = 'Documenti';

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
                        ->default(fn($get) => $get('document_type_id') ? DocumentType::find($get('document_type_id'))->name : null)
                        ->required()
                        ->columnSpanFull(),
                    Select::make('status')
                        ->label('Stato')
                        ->options(DocumentStatus::class)
                        ->default(DocumentStatus::PENDING)
                        ->required(),
                    Select::make('doctype')
                        ->label('Tipo documento')
                        ->options([
                            'modulo' => 'Modulo',
                            'procedura' => 'Procedura',
                            'template' => 'Template',
                        ]),
                    TextInput::make('cellposition')
                        ->label('Posizione cella'),
                    Toggle::make('is_monitored')
                        ->label('Controlla scadenza')
                        ->default(false)
                        ->live(),
                    DatePicker::make('emitted_at')
                        ->label('Data emissione')
                        //  ->visible(fn($get) => $get('is_monitored'))
                        ->displayFormat('d/m/Y'),
                    DatePicker::make('expires_at')
                        ->label('Data scadenza')
                        ->default(fn($get) => $get('document_type_id') ? DocumentType::find($get('document_type_id'))->durationCalculate($get('emitted_at')) : null)
                        ->displayFormat('d/m/Y')
                        ->visible(fn($get) => $get('is_monitored'))
                        ->afterOrEqual('emitted_at'),
                    TextInput::make('docnumber')
                        ->label('Numero documento')
                        ->placeholder('es. CI-2024-001'),
                    Textarea::make('description')
                        ->label('Descrizione supplementare')
                        ->rows(2)
                        ->columnSpanFull(),
                    Textarea::make('internal_notes')
                        ->label('Note interne')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),
            Section::make('File Allegato')
                ->components([
                    TextInput::make('document_url')
                        ->label('URL documento')
                        ->url(fn($record) => $record->document_url ? (str_starts_with($record->document_url, 'http') ? $record->document_url : "https://{$record->document_url}") : null),
                    SpatieMediaLibraryFileUpload::make('attachments')
                        ->label('Carica file (PDF, immagini, Word)')
                        ->multiple()
                        ->collection('documents')
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
                TextColumn::make('name')
                    ->label('Documento')
                    ->url(fn($record) => $record->getFirstMediaUrl('documents') ?: $record->document_url)
                    ->openUrlInNewTab()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->sortable(),
                TextColumn::make('emitted_at')
                    ->label('Emissione')
                    ->date('d/m/Y')
                    //  ->visible(fn($record) => $record?->is_monitored)
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label('Scadenza')
                    ->date('d/m/Y')
                    ->sortable()
                    ->visible(fn($record) => $record?->is_monitored ?? false)
                    ->color(fn($record) => $record?->expires_at?->isPast() ? 'danger' : 'gray')
                    ->weight(fn($record) => $record?->expires_at?->isPast() ? 'bold' : 'normal'),
                TextColumn::make('doctype')
                    ->sortable()
                    ->label('Tipo documento')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'modulo' => 'info',
                        'procedura' => 'warning',
                        'template' => 'success',
                        default => 'gray',
                    })
                    ->toggleable(),
                TextColumn::make('documentType.name')
                    ->label('Tipo')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('document_url')
                    ->label('URL')
                    ->url(fn($record) => $record->document_url)
                    ->openUrlInNewTab()
                    ->visible(fn($record) => !empty($record->document_url))
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
                    ->multiple()
                    ->options(DocumentStatus::class),
                SelectFilter::make('doctype')
                    ->label('Tipo documento')
                    ->multiple()
                    ->options([
                        'modulo' => 'Modulo',
                        'procedura' => 'Procedura',
                        'informativa' => 'Informativa',
                        'template' => 'Template',
                    ]),
                Filter::make('is_monitored')
                    ->label('Monitorato')
                    ->query(fn($query) => $query->where('is_monitored', true)),
                TernaryFilter::make('is_expired')
                    ->label('Scaduto')
                    ->default(false)
                    ->queries(
                        true: fn($query) => $query->where('status', DocumentStatus::EXPIRED->value),
                        false: fn($query) => $query->where('status', '!=', DocumentStatus::EXPIRED->value),
                    ),
                // TrashedFilter::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['company_id'] = $this->getOwnerRecord()->company_id
                            ?? $this->getOwnerRecord()->id;

                        return $data;
                    }),
                ExportAction::make()
                    ->exports([
                        DynamicGroupExport::make(),
                    ])
                    ->label('Esporta Excel')
                    ->color('success'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('renew')
                    ->label('Aggiorna')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    // Mostra il pulsante SOLO se il documento scade ed è monitorato
                    //    ->visible(fn(Document $record) => $record->documentType->is_monitored)
                    ->action(function (Document $record) {
                        // 1. Archiviamo il documento attuale (opzionale, dipende dal tuo DB)

                        // 2. Creiamo il nuovo documento per il rinnovo
                        $newDocument = Document::create([
                            'company_id' => $record->company_id,
                            'documentable_type' => $record->documentable_type,
                            'documentable_id' => $record->documentable_id,
                            'document_type_id' => $record->document_type_id,
                            'name' => $record->name,
                            'status' => 'pending',  // Torna in attesa del nuovo file
                            'is_monitored' => $record->is_monitored,
                            'emitted_at' => $record->expires_at,
                            //  'expires_at' => null,
                            // Verrà inserita la nuova
                            //  scadenza al caricamento
                        ]);
                        $record->update([
                            'status' => 'expired',  // o 'archived'
                        ]);
                        Notification::make()
                            ->title('Aggiornamento effettuato')
                            ->body("Nuovo aggiornamento per \"{$record->name}\".")
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
