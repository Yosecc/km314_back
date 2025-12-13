@if($record && $record->quick_access_code)
<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col items-center justify-center p-6">
            {{ ($this->showQrAction)(['record' => $record]) }}
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
@endif
