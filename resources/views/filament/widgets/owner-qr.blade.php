<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col items-center justify-center p-6">
            <x-filament::button
                x-data=""
                x-on:click="$dispatch('open-modal', { id: 'qr-modal-{{ $record->id }}' })"
                icon="heroicon-o-qr-code"
                color="info"
                size="lg"
            >
                Ver Código QR
            </x-filament::button>
        </div>
    </x-filament::section>
    
    <x-filament::modal id="qr-modal-{{ $record->id }}" width="lg">
        <x-slot name="heading">
            Código de Acceso Rápido
        </x-slot>
        
        <x-slot name="description">
            Propietario: {{ $record->first_name }} {{ $record->last_name }}
        </x-slot>
        
        <div class="flex flex-col items-center justify-center space-y-4">
            <div class="p-4 bg-white rounded-lg">
                {!! $record->generateQrCode() !!}
            </div>
            
            <div class="text-center space-y-2">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    <span class="font-semibold">Tipo:</span> Propietario
                </p>
                <p class="text-xs text-gray-500">
                    Escanea este código QR para acceso rápido
                </p>
            </div>
        </div>
        
        <x-slot name="footerActions">
            <x-filament::button
                color="gray"
                x-on:click="close"
            >
                Cerrar
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
</x-filament-widgets::widget>
