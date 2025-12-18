

<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col items-center justify-center p-6">
            <button
                x-data="{ open: false }"
                @click="open = true"
                class="filament-button filament-button--info filament-button--lg flex items-center gap-2 px-4 py-2 rounded-lg bg-info-600 text-white hover:bg-info-700"
            >
                <x-heroicon-o-information-circle class="w-5 h-5" />
                Abrir Modal
            </button>

            <template x-if="open">
                <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md relative">
                        <button @click="open = false" class="absolute top-2 right-2 text-gray-400 hover:text-gray-600">&times;</button>
                        <h2 class="text-lg font-bold mb-2">¡Modal abierto!</h2>
                        <p class="mb-4 text-sm text-gray-600">Este es un modal de prueba que sí funciona.</p>
                        <div class="flex justify-center mt-4">
                            <button @click="open = false" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Cerrar</button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
