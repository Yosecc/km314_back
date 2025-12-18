<div>
    @if($show && $record)
        <x-filament::modal wire:model="show" width="lg">
            <x-slot name="heading">
                Código de Acceso Rápido
            </x-slot>
            <x-slot name="description">
                {{ $entityType }}: {{ $record['first_name'] ?? $record['name'] ?? '' }} {{ $record['last_name'] ?? '' }}
            </x-slot>
            @include('components.qr-modal', [
                'record' => (object) $record,
                'entityType' => $entityType
            ])
            <x-slot name="footerActions">
                <x-filament::button wire:click="close" color="gray">Cerrar</x-filament::button>
            </x-slot>
        </x-filament::modal>
    @endif
</div>
