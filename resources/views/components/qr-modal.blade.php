<div class="p-6 space-y-6">
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
            onclick="copyToClipboard('{{ $record->quick_access_code }}')"
            class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700 transition-colors"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
            </svg>
            <span>Copiar Código</span>
        </button>
        
        <button 
            type="button"
            onclick="window.print()"
            class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
            </svg>
            <span>Imprimir QR</span>
        </button>

        <button 
            type="button"
            onclick="shareQR('{{ $record->getQrCodeUrl() }}', '{{ $record->quick_access_code }}')"
            class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-lg hover:bg-green-700 transition-colors"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
            </svg>
            <span>Compartir</span>
        </button>
    </div>
    
    <!-- Información adicional -->
    <div class="text-center text-xs text-gray-500 dark:text-gray-400 space-y-1 pt-4 border-t border-gray-200 dark:border-gray-700">
        <p>Escanea el código QR o ingresa el código en el formulario de entrada</p>
        <p>Este código es único y permanente para este {{ strtolower($entityType) }}</p>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        // Mostrar notificación de Filament
        new FilamentNotification()
            .title('Código copiado')
            .success()
            .send();
    }, function() {
        new FilamentNotification()
            .title('Error al copiar')
            .danger()
            .send();
    });
}

function shareQR(url, code) {
    if (navigator.share) {
        navigator.share({
            title: 'Código de Acceso Rápido',
            text: `Código de acceso: ${code}`,
            url: url
        }).then(() => {
            console.log('Compartido exitosamente');
        }).catch((error) => {
            console.log('Error al compartir:', error);
            // Fallback: copiar al portapapeles
            copyToClipboard(`${code}\n${url}`);
        });
    } else {
        // Fallback: copiar URL al portapapeles
        copyToClipboard(`${code}\n${url}`);
        new FilamentNotification()
            .title('Enlace copiado')
            .body('El código y enlace han sido copiados al portapapeles')
            .success()
            .send();
    }
}

// Estilos de impresión
window.addEventListener('beforeprint', function() {
    document.body.style.backgroundColor = 'white';
});
</script>

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
