<x-filament-panels::page>
    <div class="space-y-6">
        <form wire:submit.prevent="save">
            {{ $this->form }}
            <div class="pt-4">
                <x-filament::button type="submit">Save</x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
