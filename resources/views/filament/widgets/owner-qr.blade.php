<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col items-center justify-center p-6">
            @if($this->record)
                {{ $this->showQrAction }}
            @else
                <div class="text-center text-gray-500">
                    No se pudo cargar el propietario
                </div>
            @endif
        </div>
    </x-filament::section>
    
    <x-filament-actions::modals />
</x-filament-widgets::widget>
