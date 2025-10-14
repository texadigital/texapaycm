<x-filament-widgets::widget>
    @php($h = $this->health ?? [])
    <x-filament::section heading="System Health">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach ([
                'SafeHaven' => $h['safehaven'] ?? [],
                'PawaPay' => $h['pawapay'] ?? [],
                'OpenExchangeRates' => $h['oxr'] ?? [],
                'Queue' => $h['queue'] ?? [],
            ] as $name => $res)
                @php($ok = (bool) data_get($res, 'ok', false))
                <x-filament::card x-data="{ open: false }">
                    <div class="flex items-center justify-between">
                        <div class="font-medium">{{ $name }}</div>
                        <x-filament::badge :color="$ok ? 'success' : 'danger'">
                            {{ $ok ? 'OK' : 'Down' }}
                        </x-filament::badge>
                    </div>
                    <div class="mt-2 text-sm text-gray-600">
                        @if ($ok)
                            <span>HTTP {{ data_get($res, 'status', '200') }}</span>
                        @else
                            <span>{{ data_get($res, 'error') ?? \Illuminate\Support\Str::limit(json_encode(data_get($res, 'body')), 120) ?? 'Unavailable' }}</span>
                        @endif
                    </div>
                    <div class="mt-2">
                        <x-filament::button color="gray" size="xs" outlined x-on:click="open = !open">
                            <span x-show="!open">Details</span>
                            <span x-show="open">Hide</span>
                        </x-filament::button>
                    </div>
                    <div class="mt-2" x-show="open" x-cloak>
                        <pre class="text-xs overflow-auto">{{ json_encode($res, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                </x-filament::card>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
