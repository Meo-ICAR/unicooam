<?php
namespace App\Filament\Resources\AuditResource\RelationManagers;

use App\Enums\FindingSeverity;
use App\Enums\FindingStatus;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\ToggledFilter;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class FindingsRelationManager extends RelationManager
{
    protected static string $relationship = 'findings';

    protected static ?string $title = 'Rilievi e Non Conformità';

    /**
     * Configurazione del Form adattata all'architettura Schema.
     * Risolve il Fatal Error di compatibilità.
     */
    public function form(Schema $schema): Schema
    {
        // Richiamiamo direttamente lo schema disaccoppiato che abbiamo creato,
        // passando true per indicare che siamo dentro un Relation Manager.
        return AuditFindingForm::configure($schema, true);
    }

    // LA TABELLA DEI RILIEVI DENTRO L'AUDIT
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                // 1. GRAVITÀ (Badge automatico da Enum)
                TextColumn::make('severity')
                    ->label('Gravità')
                    ->badge()
                    ->sortable(),
                // 2. TITOLO
                TextColumn::make('title')
                    ->label('Rilievo riscontrato')
                    ->searchable()
                    ->description(fn($record) => str($record->description)->limit(50)),
                // 3. STATO (Badge automatico da Enum)
                TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->sortable(),
                // 4. SCADENZA AZIONE CORRETTIVA (Con alert visivo se scaduto)
                TextColumn::make('corrective_action_deadline')
                    ->label('Scadenza Rimedio')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn($record) => $record->isOverdue() ? 'danger' : 'gray')
                    ->weight(fn($record) => $record->isOverdue() ? 'bold' : 'normal')
                    ->description(fn($record) => $record->isOverdue() ? 'SCADUTO' : null),
                // 5. DATA RISOLUZIONE
                TextColumn::make('resolved_at')
                    ->label('Risolto il')
                    ->date('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('severity')
                    ->label('Gravità')
                    ->options(FindingSeverity::class),
                SelectFilter::make('status')
                    ->label('Stato')
                    ->options(FindingStatus::class),
                Filter::make('scaduti')
                    ->label('Rilievi Scaduti')
                    ->query(fn(Builder $query) => $query
                        ->whereIn('status', ['open', 'in_progress'])
                        ->where('corrective_action_deadline', '<', now())),
            ])
            ->headerActions([
                // Permette di creare un rilievo agganciando in automatico il company_id dell'audit madre
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Forziamo il passaggio del tenant/company_id ereditato dall'audit
                        $data['company_id'] = auth()->user()->current_company_id ?? json_decode($data['company_id'] ?? null) ?? relation_manager()->getOwnerRecord()->company_id;
                        return $data;
                    }),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
