<?php

namespace App\Filament\Traits;

use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;

/**
 * Risorse condivise che si modificano in UnicoBPM (tipi documento, plichi documentali, template email): qui si consultano soltanto,
 * con il collegamento alla scheda in UnicoBPM. Sostituisce HasPlanAccess (ne tiene la visibilità per piano). Va dichiarata nella risorsa la costante BPM_SLUG (percorso della risorsa in UnicoBPM).
 */
trait ManagedInUnicobpm
{
    use HasPlanAccess;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    /** Indirizzo dell'elenco (o della scheda del record) in UnicoBPM; null se UnicoBPM non è configurato. */
    public static function unicobpmUrl(?Model $record = null): ?string
    {
        $base = config('services.bpm.url');

        if (blank($base)) {
            return null;
        }

        return rtrim((string) $base, '/').'/admin/'.static::BPM_SLUG.($record ? '/'.$record->getKey().'/edit' : '');
    }

    /** Pulsante dell'elenco: «Gestisci in UnicoBPM». */
    public static function manageInUnicobpmAction(): Action
    {
        return Action::make('gestisciInUnicobpm')
            ->label('Gestisci in UnicoBPM')
            ->icon('heroicon-o-arrow-top-right-on-square')
            ->url(fn (): ?string => static::unicobpmUrl())
            ->openUrlInNewTab()
            ->visible(fn (): bool => static::unicobpmUrl() !== null);
    }

    /** Azione di riga: apre la scheda del record in UnicoBPM. */
    public static function openInUnicobpmAction(): Action
    {
        return Action::make('apriInUnicobpm')
            ->label('Modifica in UnicoBPM')
            ->icon('heroicon-o-arrow-top-right-on-square')
            ->url(fn (Model $record): ?string => static::unicobpmUrl($record))
            ->openUrlInNewTab()
            ->visible(fn (): bool => static::unicobpmUrl() !== null);
    }
}
