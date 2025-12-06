<div x-data="{
    copyToClipboard(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(() => {
                alert('✓ Código copiado: ' + text);
            }).catch(() => {
                this.fallbackCopy(text);
            });
        } else {
            this.fallbackCopy(text);
        }
    },
    fallbackCopy(text) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
            alert('✓ Código copiado: ' + text);
        } catch (err) {
            alert('Error al copiar. Código: ' + text);
        }
        
        document.body.removeChild(textArea);
    },
    shareToWhatsApp(url, code, entityType) {
        const mensaje = `*Código de Acceso Rápido*\n\n` +
                       `*Tipo:* ${entityType}\n` +
                       `*Código:* ${code}\n\n` +
                       `Accede mostrando este código en la entrada del barrio:\n${url}\n\n`;
        
        const mensajeCodificado = encodeURIComponent(mensaje);
        const whatsappUrl = `https://wa.me/?text=${mensajeCodificado}`;
        window.open(whatsappUrl, '_blank');
    }
}" class="p-6 space-y-6">
    <!-- QR Code -->
    <div class="flex justify-center">
        <div class="bg-white p-6 rounded-lg shadow-lg inline-block">
            {!! $qrCode !!}
        </div>
    </div>
    
    <!-- Código alfanumérico -->
    <div class="text-center space-y-2">
        <p class="text-sm text-gray-600 dark:text-gray-400">Código de acceso:</p>
        <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-4">
            <p class="text-3xl font-mono font-bold text-gray-900 dark:text-gray-100 tracking-wider">
                {{ $record->quick_access_code }}
            </p>
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-500">
            {{ $entityType }}
        </p>
    </div>
    
    <!-- Botones de acción -->
    <div class="flex flex-col sm:flex-row gap-3 justify-center">
        <button 
            type="button"
            @click.prevent="copyToClipboard('{{ $record->quick_access_code }}')"
            class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700 transition-colors cursor-pointer"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
            </svg>
            <span>Copiar Código</span>
        </button>
        
        <button 
            type="button"
            @click.prevent="copyToClipboard('{{ $record->getQrCodeUrl() }}')"
            class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors cursor-pointer"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
            </svg>
            <span>Copiar Link</span>
        </button>

        <button 
            type="button"
            @click.prevent="shareToWhatsApp('{{ $record->getQrCodeUrl() }}', '{{ $record->quick_access_code }}', '{{ $entityType }}')"
            class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-lg hover:bg-green-700 transition-colors cursor-pointer"
        >
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
            </svg>
            <span>Compartir por WhatsApp</span>
        </button>
    </div>
    
    <!-- Información adicional -->
    <div class="text-center text-xs text-gray-500 dark:text-gray-400 space-y-1 pt-4 border-t border-gray-200 dark:border-gray-700">
        <p>Escanea el código QR o ingresa el código en el formulario de entrada</p>
        <p>Este código es único y permanente para este {{ strtolower($entityType) }}</p>
    </div>
</div>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    
    .p-6.space-y-6, .p-6.space-y-6 * {
        visibility: visible;
    }
    
    .p-6.space-y-6 {
        position: absolute;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
    }
    
    button {
        display: none !important;
    }
}
</style>
