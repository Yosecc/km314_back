@if($record && $record->quick_access_code)
<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col items-center justify-center p-6">
            <x-filament::button
                x-data
                x-on:click="$wire.emit('openQrModal', @js($record), 'Propietario')"
                icon="heroicon-o-qr-code"
                color="info"
                size="lg"
            >
                Ver Código QR
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
@livewire('qr-modal')
@endif
