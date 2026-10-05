<?php

namespace App\Filament\Resources\Companies\RelationManagers;

use App\Filament\Traits\HasRelationPlanAccess;
use App\Models\ChatMessage;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ChatUsageRelationManager extends RelationManager
{
    use HasRelationPlanAccess;

    protected static string $relationship = 'chatMessages';

    protected static ?string $title = 'Utilizzo Assistente AI';

    protected static ?string $modelLabel = 'Mese';

    protected static ?string $pluralModelLabel = 'Mesi';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('period')
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withoutGlobalScope('owned')
                ->orWhereNull('company_id')
                ->selectRaw(
                    "MIN(id) AS id,
                    DATE_FORMAT(created_at, '%Y-%m') AS period,
                    COUNT(*) AS messages_count,
                    {$this->tokenSumExpression('input_tokens')} AS input_tokens,
                    {$this->tokenSumExpression('output_tokens')} AS output_tokens,
                    {$this->tokenSumExpression('cached_input_tokens')} AS cached_input_tokens,
                    {$this->tokenSumExpression('input_tokens')} + {$this->tokenSumExpression('output_tokens')} AS total_tokens"
                )
                ->groupBy('period'))
            ->defaultSort('period', 'desc')
            ->defaultKeySort(false)
            ->columns([
                TextColumn::make('period')
                    ->label('Mese')
                    ->formatStateUsing(fn (?string $state): string => $state
                        ? Carbon::createFromFormat('Y-m', $state)->translatedFormat('F Y')
                        : '-')
                    ->weight('bold'),
                TextColumn::make('messages_count')
                    ->label('Messaggi')
                    ->numeric(),
                TextColumn::make('input_tokens')
                    ->label('Token input')
                    ->numeric(),
                TextColumn::make('output_tokens')
                    ->label('Token output')
                    ->numeric(),
                TextColumn::make('cached_input_tokens')
                    ->label('Token da cache')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total_tokens')
                    ->label('Token totali')
                    ->numeric()
                    ->weight('bold')
                    ->color('primary'),
            ])
            ->filters([
                Filter::make('periodo')
                    ->label('Periodo')
                    ->form([
                        DatePicker::make('dal')
                            ->label('Dal')
                            ->displayFormat('d/m/Y'),
                        DatePicker::make('al')
                            ->label('Al')
                            ->displayFormat('d/m/Y'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['dal'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['al'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->recordActions([
                Action::make('dettaglioUtenti')
                    ->label('Dettaglio utenti')
                    ->icon('heroicon-o-users')
                    ->color('gray')
                    ->modalHeading(fn ($record): string => 'Dettaglio utenti — '.Carbon::createFromFormat('Y-m', $record->period)->translatedFormat('F Y'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Chiudi')
                    ->schema(fn ($record) => [
                        RepeatableEntry::make('utenti')
                            ->label(false)
                            ->state($this->usersBreakdownFor($record->period))
                            ->schema([
                                TextEntry::make('utente')->label('Utente'),
                                TextEntry::make('messaggi')->label('Messaggi')->numeric(),
                                TextEntry::make('token_input')->label('Token input')->numeric(),
                                TextEntry::make('token_output')->label('Token output')->numeric(),
                                TextEntry::make('token_totali')->label('Token totali')->numeric()->weight('bold'),
                            ])
                            ->columns(5),
                    ]),
            ])
            ->toolbarActions([]);
    }

    /**
     * Frammento SQL che somma un valore della sezione `usage` della colonna JSON
     * `meta` (popolata da NeuronAI con input_tokens/output_tokens/ecc. solo sui
     * messaggi dell'assistente; le altre righe producono NULL e vengono ignorate
     * automaticamente da SUM).
     */
    private function tokenSumExpression(string $usageKey): string
    {
        return "SUM(CAST(meta->>'$.usage.{$usageKey}' AS UNSIGNED))";
    }

    /**
     * Riepilogo dei token consumati per utente nel mese indicato (formato "Y-m"),
     * mostrato nel dettaglio dell'azione "Dettaglio utenti".
     *
     * @return array<int, array{utente: string, messaggi: int, token_input: int, token_output: int, token_totali: int}>
     */
    private function usersBreakdownFor(string $period): array
    {
        $companyId = $this->getOwnerRecord()->getKey();

        return ChatMessage::query()
            ->withoutGlobalScope('owned')
            ->where(fn (Builder $query) => $query->where('company_id', $companyId)->orWhereNull('company_id'))
            ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$period])
            ->selectRaw(
                "user_id,
                COUNT(*) AS messages_count,
                {$this->tokenSumExpression('input_tokens')} AS input_tokens,
                {$this->tokenSumExpression('output_tokens')} AS output_tokens"
            )
            ->groupBy('user_id')
            ->with('user:id,name,email')
            ->orderByDesc('input_tokens')
            ->get()
            ->map(fn (ChatMessage $row): array => [
                'utente' => $row->user?->name ?? 'Utente sconosciuto',
                'messaggi' => (int) $row->messages_count,
                'token_input' => (int) $row->input_tokens,
                'token_output' => (int) $row->output_tokens,
                'token_totali' => (int) $row->input_tokens + (int) $row->output_tokens,
            ])
            ->all();
    }
}
