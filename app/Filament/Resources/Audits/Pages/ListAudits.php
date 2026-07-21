<?php

namespace App\Filament\Resources\Audits\Pages;

use App\Filament\Resources\Audits\AuditResource;
use App\Models\Audit;
use App\Models\CompanyRole;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class ListAudits extends ListRecords
{
    protected static string $resource = AuditResource::class;

    /**
     * Calcola i periodi fissi per i due semestri dell'anno corrente (01/01 -> 30/06 e 01/07 -> 31/12).
     */
    private function getPeriodiSemestrali(): array
    {
        $anno = Carbon::now()->subMonths(6)->year;

        return [
            // 🔹 1° Semestre FISSO: 01/01/YYYY -> 30/06/YYYY
            's1Inizio' => Carbon::createFromDate($anno, 1, 1)->startOfDay(),
            's1Fine' => Carbon::createFromDate($anno, 6, 30)->endOfDay(),

            // 🔹 2° Semestre FISSO: 01/07/YYYY -> 31/12/YYYY
            's2Inizio' => Carbon::createFromDate($anno, 7, 1)->startOfDay(),
            's2Fine' => Carbon::createFromDate($anno, 12, 31)->endOfDay(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            Action::make('calendarioSemestrale')
                ->label('Pianificazione Semestrale Audit')
                ->icon('heroicon-o-calendar-days')
                ->color('info')
            //    ->visible(fn (Action $action) => checkPiano($action->getName()))
             //   ->modalTitle('Pianificazione e Modifica Audit Semestrali')
             //   ->modalSubmitActionLabel('Salva Modifiche')
             //   ->modalCancelActionLabel('Annulla')

                // 1. Pre-popola i campi con i valori attuali dal DB
                ->fillForm(function (): array {
                    [
                        's1Inizio' => $s1Inizio,
                        's1Fine' => $s1Fine,
                        's2Inizio' => $s2Inizio,
                        's2Fine' => $s2Fine,
                    ] = $this->getPeriodiSemestrali();

                    return [
                        'previsti_s1' => CompanyRole::auditPrevistiPerPeriodo($s1Inizio, $s1Fine),
                        'previsti_s2' => CompanyRole::auditPrevistiPerPeriodo($s2Inizio, $s2Fine),
                    ];
                })

                // 2. Form con campi editabili e conteggio effettuati
                ->form(function (): array {
                    [
                        's1Inizio' => $s1Inizio,
                        's1Fine' => $s1Fine,
                        's2Inizio' => $s2Inizio,
                        's2Fine' => $s2Fine,
                    ] = $this->getPeriodiSemestrali();

                    $effettuatiS1 = Audit::whereBetween('executed_at', [$s1Inizio, $s1Fine])->count();
                    $effettuatiS2 = Audit::whereBetween('executed_at', [$s2Inizio, $s2Fine])->count();

                    return [
                        Grid::make(2)
                            ->schema([
                                Section::make('1° Semestre')
                                    ->description($s1Inizio->format('d/m/Y').' - '.$s1Fine->format('d/m/Y'))
                                    ->icon('heroicon-o-calendar')
                                    ->schema([
                                        Placeholder::make('s1_effettuati')
                                            ->label('Audit Effettuati (Lettura)')
                                            ->content("{$effettuatiS1}"),
                                        TextInput::make('previsti_s1')
                                            ->label('Audit Previsti')
                                            ->numeric()
                                            ->minValue(0)
                                            ->required(),
                                    ])
                                    ->columnSpan(1),

                                Section::make('2° Semestre')
                                    ->description($s2Inizio->format('d/m/Y').' - '.$s2Fine->format('d/m/Y'))
                                    ->icon('heroicon-o-calendar')
                                    ->schema([
                                        Placeholder::make('s2_effettuati')
                                            ->label('Audit Effettuati (Lettura)')
                                            ->content("{$effettuatiS2}"),
                                        TextInput::make('previsti_s2')
                                            ->label('Audit Previsti')
                                            ->numeric()
                                            ->minValue(0)
                                            ->required(),
                                    ])
                                    ->columnSpan(1),
                            ]),
                    ];
                })

                // 3. Salvataggio dei dati inviati dalla form
                ->action(function (array $data): void {
                    [
                        's1Inizio' => $s1Inizio,
                        's1Fine' => $s1Fine,
                        's2Inizio' => $s2Inizio,
                        's2Fine' => $s2Fine,
                    ] = $this->getPeriodiSemestrali();

                    // Salvataggio nel DB
                    CompanyRole::salvaAuditPrevistiPerPeriodo($s1Inizio, $s1Fine, (int) $data['previsti_s1']);
                    CompanyRole::salvaAuditPrevistiPerPeriodo($s2Inizio, $s2Fine, (int) $data['previsti_s2']);

                    Notification::make()
                        ->title('Pianificazione audit aggiornata!')
                        ->success()
                        ->send();
                }),
        ];
    }
}
