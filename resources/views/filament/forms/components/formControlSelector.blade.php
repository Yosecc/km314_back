<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @if (!$isDisabled())
        @php
            // Separar formularios por estado
            $activos = collect($formularios)->filter(function($form) {
                return in_array($form['status'], ['Authorized', 'Pending']);
            })->values();
            
            $inactivos = collect($formularios)->filter(function($form) {
                return !in_array($form['status'], ['Authorized', 'Pending']);
            })->values();
        @endphp

        <div class="space-y-3" x-data="{
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }},
            mostrarInactivos: false,
            getStatusText(status) {
                const statusMap = {
                    'Pending': 'Pendiente',
                    'Authorized': 'Autorizado',
                    'Denied': 'Denegado',
                    'Vencido': 'Vencido',
                    'Expirado': 'Expirado'
                };
                return statusMap[status] || status;
            }
        }">
            
            @if($activos->isEmpty())
                <!-- Mensaje cuando no hay formularios activos -->
                <div class="text-center p-6 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border-2 border-yellow-200 dark:border-yellow-800">
                    <svg class="w-12 h-12 mx-auto mb-3 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 30px">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <p class="text-sm font-semibold text-yellow-800 dark:text-yellow-300">
                        No posee formularios activos
                    </p>
                    <p class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">
                        No hay formularios autorizados o pendientes disponibles
                    </p>
                </div>
            @else
                <!-- Formularios activos (Autorizados y Pendientes) -->
                <div class="space-y-3">
                    <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">
                        Formularios Activos ({{ $activos->count() }})
                    </div>
                    @foreach($activos as $form)
                <div
                    x-data="{ 
                        id: {{ $form['id'] }},
                        isActive: {{ $form['isActive'] ? 'true' : 'false' }},
                        status: '{{ $form['status'] }}'
                    }"
                    @click="if(isActive) { state = (state == {{ $form['id'] }} ? null : {{ $form['id'] }}) }"
                    class="relative rounded-xl p-4 shadow-sm ring-1 transition-all duration-200"
                    :class="{
                        'cursor-pointer hover:shadow-md': isActive,
                        'opacity-50 cursor-not-allowed': !isActive,
                        'ring-green-600 ring-2 bg-green-50 dark:bg-green-900/20': state == id && isActive && status === 'Authorized',
                        'ring-yellow-600 ring-2 bg-yellow-50 dark:bg-yellow-900/20': state == id && isActive && status === 'Pending',
                        'ring-red-600 ring-2 bg-red-50 dark:bg-red-900/20': state == id && isActive && status === 'Denied',
                        'ring-gray-600 ring-2 bg-gray-50 dark:bg-gray-900/20': state == id && isActive && (status === 'Vencido' || status === 'Expirado'),
                        'ring-gray-950/5 bg-white dark:bg-gray-900 dark:ring-white/10': state != id || !isActive,
                        'bg-green-50 dark:bg-green-900/10': status === 'Authorized' && state != id,
                        'bg-yellow-50 dark:bg-yellow-900/10': status === 'Pending' && state != id,
                        'bg-red-50 dark:bg-red-900/10': status === 'Denied' && state != id,
                        'bg-gray-50 dark:bg-gray-900/10': (status === 'Vencido' || status === 'Expirado') && state != id
                    }"
                >
                    <!-- Header con ID y estado -->
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-bold text-gray-700 dark:text-gray-300">
                                Formulario #{{ $form['id'] }}
                            </span>
                            @if($form['hint'])
                                <a href="/admin/form-controls/{{ $form['id'] }}" 
                                   target="_blank" 
                                   class="text-xs text-primary-600 hover:text-primary-700 hover:underline"
                                   @click.stop>
                                    Ver formulario
                                </a>
                            @endif
                        </div>
                        <span 
                            class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full"
                            :class="{
                                'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300': status === 'Authorized',
                                'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300': status === 'Pending',
                                'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300': status === 'Denied',
                                'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300': status === 'Expirado',
                                'bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-300': status === 'Vencido'
                            }"
                            x-text="getStatusText(status)"
                        >
                        </span>
                    </div>

                    <!-- Información principal -->
                    <div class="space-y-2">
                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $form['texto'] }}
                        </div>
                        
                        <div class="text-xs text-gray-600 dark:text-gray-400">
                            {{ $form['descripcion'] }}
                        </div>


                        @if( count($form['vencimientos']) && $form['vencimientos']['status'])
                        <div class="text-xs text-gray-600 dark:text-gray-400" style="background: #E74C3C;padding: 10px;border-radius: 7px;color: white;">
                            {{ $form['vencimientos']['texto'] }}
                        </div>
                        @endif

                    </div>

                    <!-- Indicador visual de selección -->
                    <div 
                        x-show="state == id && isActive"
                        x-transition
                        class="absolute top-2 right-2"
                    >
                        <svg class="w-6 h-6 text-primary-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>

                    <!-- Mensaje de deshabilitado -->
                    <div 
                        x-show="!isActive"
                        x-transition
                        class="mt-2 text-xs font-medium text-red-600 dark:text-red-400"
                    >
                        <span>Este formulario no está disponible (</span><span x-text="getStatusText(status)"></span><span>)</span>
                    </div>
                    </div>
                @endforeach
            @endif

            <!-- Acordeón para formularios inactivos -->
            @if($inactivos->isNotEmpty())
                <div class="mt-4 border-t border-gray-200 dark:border-gray-700 pt-4">
                    <!-- Botón del acordeón -->
                    <button 
                        @click="mostrarInactivos = !mostrarInactivos"
                        type="button"
                        class="w-full flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                    >
                        <div class="flex items-center gap-2">
                            <svg 
                                class="w-5 h-5 text-gray-500 transition-transform duration-200"
                                :class="{ 'rotate-90': mostrarInactivos }"
                                fill="none" 
                                stroke="currentColor" 
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                Otros formularios ({{ $inactivos->count() }})
                            </span>
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            Denegados, vencidos y expirados
                        </span>
                    </button>

                    <!-- Contenido del acordeón -->
                    <div 
                        x-show="mostrarInactivos"
                        x-collapse
                        class="space-y-3 mt-3"
                    >
                        @foreach($inactivos as $form)
                            <div
                                x-data="{ 
                                    id: {{ $form['id'] }},
                                    isActive: {{ $form['isActive'] ? 'true' : 'false' }},
                                    status: '{{ $form['status'] }}'
                                }"
                                @click="if(isActive) { state = (state == {{ $form['id'] }} ? null : {{ $form['id'] }}) }"
                                class="relative rounded-xl p-4 shadow-sm ring-1 transition-all duration-200"
                                :class="{
                                    'cursor-pointer hover:shadow-md': isActive,
                                    'opacity-50 cursor-not-allowed': !isActive,
                                    'ring-green-600 ring-2 bg-green-50 dark:bg-green-900/20': state == id && isActive && status === 'Authorized',
                                    'ring-yellow-600 ring-2 bg-yellow-50 dark:bg-yellow-900/20': state == id && isActive && status === 'Pending',
                                    'ring-red-600 ring-2 bg-red-50 dark:bg-red-900/20': state == id && isActive && status === 'Denied',
                                    'ring-gray-600 ring-2 bg-gray-50 dark:bg-gray-900/20': state == id && isActive && (status === 'Vencido' || status === 'Expirado'),
                                    'ring-gray-950/5 bg-white dark:bg-gray-900 dark:ring-white/10': state != id || !isActive,
                                    'bg-green-50 dark:bg-green-900/10': status === 'Authorized' && state != id,
                                    'bg-yellow-50 dark:bg-yellow-900/10': status === 'Pending' && state != id,
                                    'bg-red-50 dark:bg-red-900/10': status === 'Denied' && state != id,
                                    'bg-gray-50 dark:bg-gray-900/10': (status === 'Vencido' || status === 'Expirado') && state != id
                                }"
                            >
                                <!-- Header con ID y estado -->
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-bold text-gray-700 dark:text-gray-300">
                                            Formulario #{{ $form['id'] }}
                                        </span>
                                        @if($form['hint'])
                                            <a href="/admin/form-controls/{{ $form['id'] }}" 
                                               target="_blank" 
                                               class="text-xs text-primary-600 hover:text-primary-700 hover:underline"
                                               @click.stop>
                                                Ver formulario
                                            </a>
                                        @endif
                                    </div>
                                    <span 
                                        class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full"
                                        :class="{
                                            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300': status === 'Authorized',
                                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300': status === 'Pending',
                                            'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300': status === 'Denied',
                                            'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300': status === 'Expirado',
                                            'bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-300': status === 'Vencido'
                                        }"
                                        x-text="getStatusText(status)"
                                    >
                                    </span>
                                </div>

                                <!-- Información principal -->
                                <div class="space-y-2">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $form['texto'] }}
                                    </div>
                                    
                                    <div class="text-xs text-gray-600 dark:text-gray-400">
                                        {{ $form['descripcion'] }}
                                    </div>

                                    @if( count($form['vencimientos']) && $form['vencimientos']['status'])
                                    <div class="text-xs text-gray-600 dark:text-gray-400" style="background: #E74C3C;padding: 10px;border-radius: 7px;color: white;">
                                        {{ $form['vencimientos']['texto'] }}
                                    </div>
                                    @endif
                                </div>

                                <!-- Indicador visual de selección -->
                                <div 
                                    x-show="state == id && isActive"
                                    x-transition
                                    class="absolute top-2 right-2"
                                >
                                    <svg class="w-6 h-6 text-primary-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </div>

                                <!-- Mensaje de deshabilitado -->
                                <div 
                                    x-show="!isActive"
                                    x-transition
                                    class="mt-2 text-xs font-medium text-red-600 dark:text-red-400"
                                >
                                    <span>Este formulario no está disponible (</span><span x-text="getStatusText(status)"></span><span>)</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if(empty($formularios))
                <div class="text-center p-6 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        No se encontraron formularios de control disponibles
                    </p>
                </div>
            @endif
        </div>
    @else
        <!-- Vista deshabilitada (solo lectura) -->
        <div class="space-y-3">
            @foreach($formularios as $form)
                @if($form['id'] == $getState())
                    <div class="relative rounded-xl p-4 shadow-sm ring-1 ring-gray-950/5 bg-gray-50 dark:bg-gray-800 dark:ring-white/10">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-bold text-gray-700 dark:text-gray-300">
                                Formulario #{{ $form['id'] }}
                            </span>
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">
                                @php
                                    $statusMap = [
                                        'Pending' => 'Pendiente',
                                        'Authorized' => 'Autorizado',
                                        'Denied' => 'Denegado',
                                        'Vencido' => 'Vencido',
                                        'Expirado' => 'Expirado'
                                    ];
                                    echo $statusMap[$form['status']] ?? $form['status'];
                                @endphp
                            </span>
                        </div>
                        <div class="space-y-2">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $form['texto'] }}
                            </div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">
                                {{ $form['descripcion'] }}
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @endif
</x-dynamic-component>
