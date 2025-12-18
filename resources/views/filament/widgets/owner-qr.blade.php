
@if($record && $record->quick_access_code)
<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col items-center justify-center p-6">
            <button
                x-data="{ open: false }"
                @click="open = true"
                class="filament-button filament-button--info filament-button--lg flex items-center gap-2 px-4 py-2 rounded-lg bg-info-600 text-white hover:bg-info-700"
            >
                <x-heroicon-o-qr-code class="w-5 h-5" />
                Ver Código QR
            </button>

            <template x-if="open">
                <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md relative">
                        <button @click="open = false" class="absolute top-2 right-2 text-gray-400 hover:text-gray-600">&times;</button>
                        <h2 class="text-lg font-bold mb-2">Código de Acceso Rápido</h2>
                        <p class="mb-4 text-sm text-gray-600">Propietario: {{ $record->first_name }} {{ $record->last_name }}</p>
                        <div class="flex justify-center mb-4">
                            <div class="bg-white p-4 rounded-lg shadow">
                                {!! $record->generateQrCodeForScanner() !!}
                            </div>
                        </div>
                        <div class="text-center space-y-2 mb-4">
                            <p class="text-sm text-gray-600">Código de acceso:</p>
                            <div class="bg-gray-100 rounded-lg p-2">
                                <p class="text-2xl font-mono font-bold text-gray-900 tracking-wider">
                                    {{ $record->quick_access_code }}
                                </p>
                            </div>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-2 justify-center mb-2">
                            <button type="button" @click="navigator.clipboard.writeText('{{ $record->quick_access_code }}'); alert('Código copiado')" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300 text-sm">Copiar Código</button>
                            <button type="button" @click="navigator.clipboard.writeText('{{ $record->getQrCodeUrl() }}'); alert('Link copiado')" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">Copiar Link</button>
                            <button type="button" @click="window.open('https://wa.me/?text=' + encodeURIComponent('Código de Acceso Rápido%0A%0ATipo: Propietario%0ACódigo: {{ $record->quick_access_code }}%0A%0AAccede mostrando este código en la entrada del barrio:%0A{{ $record->getQrCodeUrl() }}'), '_blank')" class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 text-sm">Compartir por WhatsApp</button>
                        </div>
                        <div class="text-center text-xs text-gray-500 mt-2">
                            Escanea el código QR o ingresa el código en el formulario de entrada.<br>
                            Este código es único y permanente para este propietario.
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
@endif
