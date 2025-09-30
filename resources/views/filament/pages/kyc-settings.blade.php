<x-filament::page>
    <div>
        {{ $this->form }}
    </div>

    <x-filament::section>
        <x-filament::button color="primary" wire:click="save">
            Save Settings
        </x-filament::button>
    </x-filament::section>
</x-filament::page>
