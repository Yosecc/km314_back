


<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-row items-center justify-center gap-8 p-6">
            <!-- QR a la izquierda -->
            <div class="bg-white p-2 rounded-lg shadow flex-shrink-0 text-center">
                {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(120)->margin(1)->generate($record->quick_access_code) !!}
                <div class="text-xs text-gray-500 mt-2">Presente este QR en la entrada para tener acceso al barrio</div>
            </div>

            <!-- Opciones a la derecha -->
            <div class="flex flex-col gap-4 items-start">
                <div>
                    <span class="text-sm text-gray-600">Código de acceso:</span>
                    <div class="rounded-lg p-2 mt-1">
                        <span class="text-2xl font-mono font-bold text-gray-900 tracking-wider">{{ $record->quick_access_code }}</span>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="button" @click="navigator.clipboard.writeText('{{ $record->quick_access_code }}'); alert('Código copiado')" class="px-3 py-1  rounded hover:bg-gray-300 text-sm">Copiar Código</button>
                    <button type="button" @click="navigator.clipboard.writeText('{{ $record->getQrCodeUrl() }}'); alert('Link copiado')" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">Copiar Link</button>
                    <button type="button" @click="window.open('https://wa.me/?text=' + encodeURIComponent('Código de Acceso Rápido%0A%0ATipo: Propietario%0ACódigo: {{ $record->quick_access_code }}%0A%0AAccede mostrando este código en la entrada del barrio:%0A{{ $record->getQrCodeUrl() }}'), '_blank')" class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 text-sm">Compartir por WhatsApp</button>
                </div>
            </div>
            
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
