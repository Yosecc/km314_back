@if($record && $record->quick_access_code)
<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col items-center justify-center p-6">
            <x-filament::button
                x-data=""
                x-on:click="$dispatch('open-modal', { id: 'qr-owner-modal' })"
                icon="heroicon-o-qr-code"
                color="info"
                size="lg"
            >
                Ver Código QR
            </x-filament::button>
        </div>
    </x-filament::section>
    
    <x-filament::modal id="qr-owner-modal" width="lg">
        <x-slot name="heading">
            Código de Acceso Rápido
        </x-slot>
        
        <x-slot name="description">
            Propietario: {{ $record->first_name }} {{ $record->last_name }}
        </x-slot>
        
        @include('components.qr-modal', [
            'record' => $record,
            'entityType' => 'Propietario'
        ])
        
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
@endif
