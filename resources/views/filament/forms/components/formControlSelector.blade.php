<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @if (!$isDisabled())
        <div class="space-y-3" x-data="{state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }} }">
            @foreach($formularios as $form)
                <div
                    x-data="{ 
                        id: {{ $form['id'] }},
                        isActive: {{ $form['isActive'] ? 'true' : 'false' }},
                        status: '{{ $form['status'] }}'
                    }"
                    @click="if(isActive) { state = {{ $form['id'] }} }"
                    class="relative rounded-xl p-4 shadow-sm ring-1 transition-all duration-200"
                    :class="{
                        'cursor-pointer hover:shadow-md': isActive,
                        'opacity-50 cursor-not-allowed': !isActive,
                        'ring-primary-600 ring-2 bg-primary-50 dark:bg-primary-900/20': state == id && isActive,
                        'ring-gray-950/5 bg-white dark:bg-gray-900 dark:ring-white/10': state != id || !isActive
                    }"
                    :style="!isActive && { 
                        backgroundColor: status === 'Expirado' ? 'rgb(254 242 242)' : 
                                       status === 'Vencido' ? 'rgb(254 249 195)' : 
                                       status === 'Denied' ? 'rgb(254 226 226)' : 
                                       'rgb(243 244 246)' 
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
                                'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300': status === 'Active',
                                'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300': status === 'Pending',
                                'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300': status === 'Denied',
                                'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300': status === 'Expirado',
                                'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300': status === 'Vencido'
                            }"
                        >
                            @{{ status }}
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
                        Este formulario no está disponible (@{{ status }})
                    </div>
                </div>
            @endforeach

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
                                {{ $form['status'] }}
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
