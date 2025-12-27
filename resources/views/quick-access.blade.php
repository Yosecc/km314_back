<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Rápido - KM314</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body class="flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-2xl overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-purple-600 to-indigo-600 p-6 text-white">
            <div class="flex items-center justify-center mb-2">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-center">Código de Acceso Verificado</h1>
            <p class="text-center text-purple-100 mt-1">✓ Código válido</p>
        </div>

        <!-- Content -->
        <div class="p-6 space-y-6">
            <!-- Tipo de entidad -->
            <div class="bg-gray-50 rounded-lg p-4 border-l-4 border-purple-600">
                <p class="text-sm text-gray-600 mb-1">Tipo de acceso</p>
                <p class="text-xl font-bold text-gray-900">{{ $entityType }}</p>
            </div>

            <!-- Información de la persona/formulario -->
            <div class="space-y-3">
                @if($entity instanceof \App\Models\Employee || $entity instanceof \App\Models\Owner)
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Nombre</p>
                            <p class="font-semibold text-gray-900">{{ $entity->first_name }} {{ $entity->last_name }}</p>
                        </div>
                    </div>

                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">DNI</p>
                            <p class="font-semibold text-gray-900">{{ $entity->dni }}</p>
                        </div>
                    </div>
                @else
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Formulario</p>
                            <p class="font-semibold text-gray-900">#{{ $entity->id }}</p>
                        </div>
                    </div>
                @endif

                <!-- QR Code -->
                <div class="flex justify-center my-4">
                    <div class="bg-white p-4 rounded-lg shadow-lg inline-block border-2 border-purple-200">
                        {!! $entity->generateQrCodeForScanner() !!}
                    </div>
                </div>

                <!-- Código -->
                <div class="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-lg p-4 border border-purple-200">
                    <p class="text-sm text-gray-600 mb-2 text-center">Código de acceso</p>
                    <p class="text-2xl font-mono font-bold text-center text-purple-900 tracking-wider">{{ $code }}</p>
                </div>
            </div>

            <!-- Instrucciones -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-start space-x-3">
                    <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <p class="text-sm font-semibold text-blue-900 mb-1">Muestra este código en la entrada del barrio para acceder al mismo</p>
                        
                        
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="bg-gray-50 px-6 py-4 text-center text-xs text-gray-500">
            <p>🔒 Enlace seguro y encriptado</p>
            <p class="mt-1">KM314 - Sistema de Control de Acceso</p>
        </div>
    </div>
</body>
</html>
