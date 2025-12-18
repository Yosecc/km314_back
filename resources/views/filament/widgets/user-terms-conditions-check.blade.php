<x-filament-widgets::widget>
    <x-filament::section>
        <form x-data="{ accepted: false }" @submit.prevent="$wire.acceptTerms()" @terms-accepted.window="accepted = false">
            <label class="flex items-center space-x-2">
                <input type="checkbox" x-model="accepted" class="form-checkbox">
                <span class="text-sm">Acepto los <a href="/terminos-y-condiciones" class="underline text-primary-600" target="_blank">términos y condiciones</a></span>
            </label>
            <button type="submit" class="mt-4 filament-button filament-button--primary" :disabled="!accepted">Continuar</button>
        </form>
    </x-filament::section>
</x-filament-widgets::widget>
