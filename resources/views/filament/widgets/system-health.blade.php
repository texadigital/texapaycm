<x-filament-widgets::widget>
    <x-filament::section heading="System Health">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @php($h = $this->health ?? [])
            <x-filament::section heading="SafeHaven">
                <pre class="text-xs overflow-auto">{{ json_encode($h['safehaven'] ?? [], JSON_PRETTY_PRINT) }}</pre>
            </x-filament::section>
            <x-filament::section heading="PawaPay">
                <pre class="text-xs overflow-auto">{{ json_encode($h['pawapay'] ?? [], JSON_PRETTY_PRINT) }}</pre>
            </x-filament::section>
            <x-filament::section heading="OpenExchangeRates">
                <pre class="text-xs overflow-auto">{{ json_encode($h['oxr'] ?? [], JSON_PRETTY_PRINT) }}</pre>
            </x-filament::section>
            <x-filament::section heading="Queue">
                <pre class="text-xs overflow-auto">{{ json_encode($h['queue'] ?? [], JSON_PRETTY_PRINT) }}</pre>
            </x-filament::section>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
