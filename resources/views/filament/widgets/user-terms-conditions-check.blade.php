<x-filament-widgets::widget>
    <x-filament::section>
        <form x-data="{ accepted: false }" @submit.prevent="$wire.acceptTerms()" @terms-accepted.window="accepted = false" class="max-w-md mx-auto bg-white rounded-lg shadow p-6 flex flex-col gap-4">
            <div>
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" x-model="accepted" class="form-checkbox mt-1 h-5 w-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                    <span class="text-sm text-gray-700">
                        He leído y acepto los
                        <a href="/terminos-y-condiciones?id=1" target="_blank" class="text-primary-600 hover:underline">términos y condiciones</a>
                    </span>
                </label>
            </div>
            <button type="submit" class="filament-button filament-button--primary w-full" :disabled="!accepted">
                Continuar
            </button>
        </form>
    </x-filament::section>
</x-filament-widgets::widget>
