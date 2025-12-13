<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col items-center justify-center p-6">
            <x-filament::button
                wire:click="mountAction('show_qr')"
                icon="heroicon-o-qr-code"
                color="info"
                size="lg"
            >
                Ver Código QR
            </x-filament::button>
        </div>
    </x-filament::section>
    
    <x-filament-actions::modals />
</x-filament-widgets::widget>
