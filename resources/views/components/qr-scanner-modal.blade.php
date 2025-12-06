<div id="qr-scanner-modal" class="qr-scanner-modal" style="display: none;">
    <div class="qr-scanner-overlay">
        <div class="qr-scanner-container">
            <!-- Header -->
            <div class="qr-scanner-header">
                <h3 style="color: white; margin: 0;">Escanear Código QR</h3>
                <button onclick="stopQrScanner()" class="qr-scanner-close">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 24px; height: 24px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <!-- Scanner Area -->
            <div id="qr-reader" class="qr-reader"></div>
            
            <!-- Instructions -->
            <div class="qr-scanner-instructions">
                <p>Coloque el código QR frente a la cámara</p>
            </div>
        </div>
    </div>
</div>

<style>
.qr-scanner-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 9999;
}

.qr-scanner-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.95);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.qr-scanner-container {
    width: 100%;
    max-width: 500px;
    background: #1f2937;
    border-radius: 12px;
    overflow: hidden;
}

.qr-scanner-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background: #111827;
}

.qr-scanner-close {
    background: transparent;
    border: none;
    color: white;
    cursor: pointer;
    padding: 8px;
    border-radius: 6px;
    transition: background 0.2s;
}

.qr-scanner-close:hover {
    background: rgba(255, 255, 255, 0.1);
}

.qr-reader {
    width: 100%;
    min-height: 300px;
    background: #000;
}

#qr-reader video {
    width: 100%;
    height: auto;
}

.qr-scanner-instructions {
    padding: 20px;
    text-align: center;
}

.qr-scanner-instructions p {
    color: #9ca3af;
    margin: 0;
    font-size: 14px;
}

/* Responsive */
@media (max-width: 640px) {
    .qr-scanner-container {
        max-width: 100%;
        height: 100%;
        border-radius: 0;
    }
    
    .qr-reader {
        min-height: 60vh;
    }
}
</style>

<!-- html5-qrcode Library -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>
let html5QrcodeScanner = null;

function startQrScanner() {
    const modal = document.getElementById('qr-scanner-modal');
    modal.style.display = 'block';
    
    // Initialize scanner
    if (!html5QrcodeScanner) {
        html5QrcodeScanner = new Html5Qrcode("qr-reader");
    }
    
    const config = {
        fps: 10,
        qrbox: { width: 250, height: 250 },
        aspectRatio: 1.0
    };
    
    html5QrcodeScanner.start(
        { facingMode: "environment" }, // Cámara trasera
        config,
        onScanSuccess,
        onScanError
    ).catch(err => {
        console.error('Error starting scanner:', err);
        alert('Error al iniciar la cámara. Por favor, verifica los permisos.');
        stopQrScanner();
    });
}

function stopQrScanner() {
    const modal = document.getElementById('qr-scanner-modal');
    modal.style.display = 'none';
    
    if (html5QrcodeScanner) {
        html5QrcodeScanner.stop().then(() => {
            console.log('Scanner stopped');
        }).catch(err => {
            console.error('Error stopping scanner:', err);
        });
    }
}

function onScanSuccess(decodedText, decodedResult) {
    console.log('QR Code scanned:', decodedText);
    
    // Vibrar si está disponible
    if (navigator.vibrate) {
        navigator.vibrate(200);
    }
    
    // Encontrar el campo quick_code
    const quickCodeInput = document.querySelector('input[name="quick_code"]');
    
    if (quickCodeInput) {
        // Establecer el valor
        quickCodeInput.value = decodedText;
        
        // Disparar eventos para que Livewire detecte el cambio
        quickCodeInput.dispatchEvent(new Event('input', { bubbles: true }));
        quickCodeInput.dispatchEvent(new Event('change', { bubbles: true }));
        quickCodeInput.dispatchEvent(new Event('blur', { bubbles: true }));
        
        // Cerrar el scanner
        stopQrScanner();
        
        // Notificación visual
        if (window.Filament) {
            new FilamentNotification()
                .title('Código escaneado')
                .success()
                .send();
        }
    } else {
        console.error('Campo quick_code no encontrado');
        alert('Error: Campo de código no encontrado');
        stopQrScanner();
    }
}

function onScanError(errorMessage) {
    // Silenciar errores de escaneo (son normales mientras busca el QR)
    // console.log('Scanning...', errorMessage);
}

// Cerrar con tecla ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modal = document.getElementById('qr-scanner-modal');
        if (modal && modal.style.display === 'block') {
            stopQrScanner();
        }
    }
});

// Limpiar al cerrar la página
window.addEventListener('beforeunload', function() {
    if (html5QrcodeScanner) {
        html5QrcodeScanner.stop();
    }
});
</script>
