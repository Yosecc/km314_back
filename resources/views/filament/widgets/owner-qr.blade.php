@if($record && $record->quick_access_code)
<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col items-center justify-center p-6">
            <x-filament::button
                wire:click="openModal"
                icon="heroicon-o-qr-code"
                color="info"
                size="lg"
            >
                Ver Código QR
            </x-filament::button>
        </div>
    </x-filament::section>
    
    @if($showModal)
    <x-filament::modal wire:model="showModal" width="lg">
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
                wire:click="closeModal"
                color="gray"
            >
                Cerrar
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
    @endif
</x-filament-widgets::widget>
@endif
