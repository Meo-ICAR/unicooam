<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold">Generazione Relazione Semestrale OAM</h2>
                <p class="text-sm text-gray-500">Seleziona il periodo per compilare automaticamente i 5 fogli Excel richiesti dall'organismo.</p>
            </div>

            <div class="flex items-center gap-4">
                <!-- Qui andranno i campi per Anno e Semestre (tramite Action di Filament o Form) -->
                <x-filament::button color="primary" icon="heroicon-m-arrow-down-tray">
                    Genera Excel OAM
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
