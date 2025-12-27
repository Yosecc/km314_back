<form wire:submit.prevent="authenticate" class="space-y-6">
    {{ $this->form }}
    <div>
        <label class="flex items-center space-x-2">
            <input type="checkbox" wire:model.defer="terms" class="form-checkbox">
            <span class="text-sm">Acepto los <a href="#" class="underline" target="_blank">términos y condiciones</a></span>
        </label>
        @error('terms')
            <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
        @enderror
    </div>
    <button type="submit" class="w-full filament-button filament-button--primary">Iniciar sesión</button>
</form>
