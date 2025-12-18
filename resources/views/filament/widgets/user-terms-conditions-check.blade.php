<x-filament-widgets::widget>
    <x-filament::section>
        <form x-data="{ accepted: false }" @submit.prevent="$wire.acceptTerms()" @terms-accepted.window="accepted = false" class="max-w-md mx-auto rounded-lg shadow p-6 flex flex-col gap-4">
            <div>
                <h3>Para acceder a todas las opciones de navegación, por favor acepta los términos y condiciones.</h3>
                <label class="flex items-start gap-3 cursor-pointer mt-6">
                    <input type="checkbox" x-model="accepted" class="form-checkbox mt-1 h-5 w-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                    <span class="text-sm ">
                        He leído y acepto los
                        <a href="/terminos-y-condiciones?id=1" target="_blank" class="text-primary-600 hover:underline">términos y condiciones</a>
                    </span>
                </label>
            </div>
            <style>
                .bg-blue {
                    background-color: #3b82f6;
                }
                .bg-blue:hover {
                    background-color: #2563eb;
                }
                .bg-blue:disabled {
                    background-color: #93c5fd;
                    cursor: not-allowed;
                }
            </style>
            <button type="submit" class="px-3 py-1 bg-blue text-white rounded hover:bg-blue-700 text-sm" :disabled="!accepted">
                Continuar
            </button>
        </form>
    </x-filament::section>
</x-filament-widgets::widget>
