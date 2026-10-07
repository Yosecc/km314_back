


@php
    $quickAccessUrl = method_exists($record, 'getPlainQrCodeUrl')
        ? $record->getPlainQrCodeUrl()
        : url('/quick-access/' . $record->quick_access_code);
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <div
            x-data="{
                copy(text, message) {
                    const fallbackCopy = () => {
                        const input = document.createElement('textarea');
                        input.value = text;
                        input.style.position = 'fixed';
                        input.style.left = '-9999px';
                        document.body.appendChild(input);
                        input.select();
                        document.execCommand('copy');
                        document.body.removeChild(input);
                    };

                    if (navigator.clipboard?.writeText) {
                        navigator.clipboard.writeText(text).catch(fallbackCopy);
                    } else {
                        fallbackCopy();
                    }

                    alert(message);
                },
                shareWhatsApp(url, code) {
                    const message = `*Código de Acceso Rápido*\n\n*Tipo:* Propietario\n*Código:* ${code}\n\nAccede mostrando este código en la entrada del barrio:\n${url}`;
                    window.open(`https://wa.me/?text=${encodeURIComponent(message)}`, '_blank');
                },
            }"
        >
        <div class="flex flex-col md:flex-row items-center justify-center gap-6 md:gap-8 p-4 md:p-6">
            <!-- QR a la izquierda -->
            <div class="bg-white p-2 rounded-lg shadow flex-shrink-0 text-center mb-4 md:mb-0">
                {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(100)->margin(1)->generate($record->quick_access_code) !!}
            </div>

            <!-- Opciones a la derecha -->
            <div class="flex flex-col gap-4 items-center md:items-start w-full max-w-xs">
                <div class="w-full">
                    <span class="text-sm text-gray-600">Código de acceso:</span>
                    <div class="rounded-lg p-2 mt-1 text-center md:text-left">
                        <span class="text-2xl font-mono font-bold text-gray-900 tracking-wider">{{ $record->quick_access_code }}</span>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row flex-wrap gap-2 w-full justify-center md:justify-start">
                    <button type="button" @click="copy(@js($record->quick_access_code), 'Código copiado')" class="px-3 py-2 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        Copiar código
                    </button>
                    <button type="button" @click="copy(@js($quickAccessUrl), 'Enlace copiado')" class="px-3 py-2 rounded-lg bg-primary-600 text-sm font-medium text-white hover:bg-primary-700">
                        Copiar enlace
                    </button>
                    <button type="button" @click="shareWhatsApp(@js($quickAccessUrl), @js($record->quick_access_code))" class="px-3 py-2 rounded-lg bg-green-600 text-sm font-medium text-white hover:bg-green-700">
                        Compartir por WhatsApp
                    </button>
                </div>
            </div>
        </div>
        <div class="w-full text-center">
            <div class="text-xs text-gray-500">Presente este QR en la entrada para tener acceso al barrio</div>
        </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
