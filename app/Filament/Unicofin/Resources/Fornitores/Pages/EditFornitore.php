<?php

namespace App\Filament\Unicofin\Resources\Fornitores\Pages;

use App\Filament\Unicofin\Resources\Fornitores\FornitoreResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditFornitore extends EditRecord
{
    protected static string $resource = FornitoreResource::class;

    protected function getHeaderActions(): array
    {
        return [

            Action::make('revokeAllDocuments')
                ->label('Revoca tutti i documenti')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Revoca di massa dei documenti')
                ->modalDescription('Sei sicuro di voler contrassegnare come REVOCATI tutti i documenti associati a questo agente? Questa azione non è reversibile.')

                // Il tasto compare SOLO se il rapporto è terminato (dismissed_at non è null)
                // E se ci sono documenti non ancora revocati da elaborare
                ->visible(fn ($record) => $record->dismissed_at !== null &&
                    $record->documents()->where('status', '!=', 'REVOKED')->exists()
                )

                ->action(function ($record) {
                    // Eseguiamo l'operazione in transazione per sicurezza
                    DB::transaction(function () use ($record) {

                        // Aggiornamento massivo di tutti i documenti dell'agente
                        $record->documents()->update([
                            'status' => 'REVOKED', // O DocumentStatus::REVOKED->value se usi un Enum
                            'updated_by' => auth()->id(),
                        ]);

                        // Opzionale: Tracciabilità sul fornitore stesso
                        // $record->timestamps = false; // per evitare di alterare l'updated_at del fornitore se non desiderato
                    });

                    Notification::make()
                        ->title('Documenti revocati')
                        ->body('Tutti i documenti dell’agente sono stati impostati in stato REVOKED.')
                        ->success()
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }
}
