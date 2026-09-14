{{-- Intestazione della pagina "Semestrale OAM": il titolo va sempre su una
     riga separata rispetto ai tasti header actions, che sono molti e
     altrimenti spingono il titolo (facendolo andare a capo) quando la
     larghezza disponibile è quella di un desktop. --}}
<x-filament-panels::header
    :actions="$actions"
    :actions-alignment="$actionsAlignment"
    :breadcrumbs="$breadcrumbs"
    :heading="$heading"
    :subheading="$subheading"
    style="flex-direction: column; align-items: flex-start;"
/>
